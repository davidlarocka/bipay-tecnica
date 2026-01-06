# Gestión de usuarios y transacciones financiera API 💰

Sistema de billetera digital para gestión de saldos, transferencias entre usuarios y reportes financieros. Desarrollado con una arquitectura robusta para garantizar la integridad de las transacciones.

## 🚀 Stack Tecnológico

* **Framework:** Laravel 11.x
* **Lenguaje:** PHP 8.2+
* **Base de Datos:** MySQL 8.0
* **Entorno:** Docker & Docker Compose
* **Documentación:** Laravel Scribe
* **Seguridad:** Laravel Sanctum (Auth API)

## 🐳 Entorno Docker

El proyecto utiliza un entorno contenedorizado para facilitar el despliegue:
* `laravel_app`: Contenedor PHP 8.2 (Puerto 8081).
* `mysql_db`: Contenedor MySQL (Puerto 3306).



## 🛠 Instalación y Configuración

1.  **Clonar el repositorio:**
    ```bash
    git clone [https://github.com/tu-usuario/wallet-api.git](https://github.com/tu-usuario/wallet-api.git)
    cd wallet-api
    ```

2.  **Configurar Variables de Entorno:**
    Crea un archivo `.env` dentro de `backend/laravel/` con la siguiente configuración de base de datos para Docker:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=db
    DB_PORT=3306
    DB_DATABASE=laravel
    DB_USERNAME=laravel
    DB_PASSWORD=laravel
    ```

3.  **Levantar el entorno:**
    ```bash
    docker-compose up -d
    ```

4.  **Inicializar la aplicación:**
    ```bash
    docker exec -it laravel_app composer install
    docker exec -it laravel_app php artisan key:generate
    docker exec -it laravel_app php artisan migrate --seed
    ```

## 🛣 Rutas de la API (Endpoints)

### Públicas
* `POST /api/register` - Registro de usuario.
* `POST /api/login` - Inicio de sesión y obtención de token.

### Privadas (Middleware: `auth:passport`)
* `GET /api/me` - Perfil del usuario autenticado.
* `POST /api/transfer` - Enviar saldo a otro usuario (Límite diario: 5,000).
* `GET /api/users/balances/csv` - Descarga de reporte en CSV (Delimitador `;`).
* `GET /api/users/total-transferred` - Reporte de totales enviados por usuario.
* `GET /api/users/average-transferred` - Reporte de promedios de transferencia.



## 🧪 Pruebas Unitarias (PHPUnit)

Se han implementado tests para cubrir la lógica de negocio (saldos, límites y transferencias):

```bash
docker exec -it laravel_app php artisan test
 ```



## 📄 Documentación Interactiva

* La documentación se genera automáticamente mediante Scribe.

* Generar: docker exec -it laravel_app php artisan scribe:generate

* Visualizar: Accede a http://localhost:8081/docs 