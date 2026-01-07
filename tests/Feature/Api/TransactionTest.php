<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_transaction_with_correct_attributes()
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        $transaction = Transaction::create([
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'amount' => 100,
        ]);

        $this->assertEquals($fromUser->id, $transaction->from_user_id);
        $this->assertEquals($toUser->id, $transaction->to_user_id);
        $this->assertEquals(100, $transaction->amount);
    }

    /** @test */
    public function it_returns_the_correct_from_user_relationship()
    {
        $fromUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'from_user_id' => $fromUser->id,
        ]);

        $this->assertInstanceOf(User::class, $transaction->fromUser);
        $this->assertEquals($fromUser->id, $transaction->fromUser->id);
    }

    /** @test */
    public function it_returns_the_correct_to_user_relationship()
    {
        $toUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'to_user_id' => $toUser->id,
        ]);

        $this->assertInstanceOf(User::class, $transaction->toUser);
        $this->assertEquals($toUser->id, $transaction->toUser->id);
    }
}