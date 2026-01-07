<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Response;

/**
 * @group Gestión de Billetera
 *
 * APIs para manejar saldos, transferencias y reportes de usuarios.
 */
class WalletController extends Controller
{
    /**
     * Exportar Usuarios a CSV
     * * Genera y descarga un archivo CSV con el listado de todos los usuarios
     * y sus saldos actuales, usando ";" como delimitador.
     * * @endpoint GET api/users/balances/csv
     * @responseField string Nombre El nombre completo del usuario.
     * @response 200 (binary/csv)
     */
    public function exportUsersCsv()
    {
        $fileName = 'usuarios_saldos.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');


            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Encabezados usando ";" como delimitador
            fputcsv($file, ['Nombre', 'Email', 'Saldo'], ';');

            foreach (User::cursor() as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->saldo
                ], ';');
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    /**
     * Promedio de transferencias
     * * Obtiene el valor medio de las transferencias enviadas por cada usuario que ha operado.
     * * @queryParam sort string Campo por el que ordenar (promedio_transferido o total_transacciones). Default: promedio_transferido.
     */
    public function getTotalTransferredPerUser()
    {
        // Realizamos la suma agrupada por el ID del emisor
        $totals = Transaction::query()
            ->select('from_user_id', DB::raw('SUM(amount) as total_enviado'))
            ->with('fromUser:id,name,email') // Usamos el nombre exacto de tu relación
            ->groupBy('from_user_id')
            ->orderBy('total_enviado', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $totals->map(function ($transaction) {
                return [
                    'usuario_id' => $transaction->from_user_id,
                    'nombre' => $transaction->fromUser->name ?? 'Desconocido',
                    'email' => $transaction->fromUser->email ?? 'N/A',
                    'total_transferido' => (float) $transaction->total_enviado,
                ];
            })
        ]);
    }

    public function getAverageTransferredPerUser()
    {
        // Calculamos el promedio (AVG) y el conteo (COUNT) para dar más contexto
        $averages = Transaction::query()
            ->select(
                'from_user_id',
                DB::raw('AVG(amount) as promedio_transferido'),
                DB::raw('COUNT(*) as total_transacciones')
            )
            ->with('fromUser:id,name,email')
            ->groupBy('from_user_id')
            ->orderBy('promedio_transferido', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $averages->map(function ($transaction) {
                return [
                    'usuario_id' => $transaction->from_user_id,
                    'nombre' => $transaction->fromUser->name ?? 'Desconocido',
                    'email' => $transaction->fromUser->email ?? 'N/A',
                    'promedio_transferido' => round((float) $transaction->promedio_transferido, 2),
                    'numero_de_envios' => $transaction->total_transacciones,
                ];
            })
        ]);
    }

    /**
     * Realizar Transferencia
     * * Permite enviar dinero de la cuenta del usuario autenticado a otro usuario mediante su email.
     * * <small class="badge badge-warning">Restricción</small> Límite diario de 5,000 unidades.
     * * @bodyParam email string required El correo electrónico del destinatario. Example: receptor@correo.com
     * @bodyParam amount number required El monto a transferir. Debe ser mayor a 0. Example: 150.50
     * * @response 200 {
     * "message": "Transferencia realizada con éxito",
     * "transaction": {"id": 1, "uuid": "...", "amount": 150.50},
     * "saldo": {"before": 1000, "after": 849.50}
     * }
     * @response 422 {
     * "message": "Límite diario excedido"
     * }
     */

    public function transfer(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $from = $request->user();
        $to = User::where('email', $request->email)->first();
        $amount = (float) $request->amount;

        // 1. Validación: No enviarse a sí mismo
        if ($from->id === $to->id) {
            return response()->json(['message' => 'No puedes enviarte saldo a ti mismo'], 422);
        }

        // 2. NUEVA VALIDACIÓN: Límite diario de 5000
        // Sumamos el monto de las transacciones enviadas por el usuario hoy
        $totalEnviadoHoy = Transaction::where('from_user_id', $from->id)
            ->whereDate('created_at', now()->today())
            ->sum('amount');

        if (($totalEnviadoHoy + $amount) > 5000) {
            return response()->json([
                'message' => 'Límite diario excedido, intente mañana',
                'detalles' => [
                    'limite_diario' => 5000,// se puede mejorar con una variable de configuración
                    'enviado_hoy' => $totalEnviadoHoy,
                    'disponible_hoy' => 5000 - $totalEnviadoHoy
                ]
            ], 422);
        }

        // 3. Validación: Saldo suficiente
        if ($from->saldo < $amount) {
            return response()->json(['message' => 'Saldo insuficiente'], 422);
        }

        $saldoBefore = $from->saldo;

        try {
            $transactionData = DB::transaction(function () use ($from, $to, $amount) {
                // Bloqueo de fila para evitar doble gasto (Race Condition)
                $sender = User::where('id', $from->id)->lockForUpdate()->first();

                if ($sender->saldo < $amount) {
                    throw new \Exception('Saldo insuficiente detectado.');
                }

                $sender->decrement('saldo', $amount);
                $to->increment('saldo', $amount);

                return Transaction::create([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'from_user_id' => $sender->id,
                    'to_user_id' => $to->id,
                    'amount' => $amount,
                ]);
            });

            $from->refresh();

            return response()->json([
                'message' => 'Transferencia realizada con éxito',
                'transaction' => $transactionData,
                'saldo' => [
                    'antes' => $saldoBefore,
                    'despues' => $from->saldo,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error en el servidor: ' . $e->getMessage()], 500);
        }
    }
}
