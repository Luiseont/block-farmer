<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Wallet\Chains\TaikoChain;

class SendTaikoTx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-taiko-tx';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send taiko transaction ETH-WETH';

    /**
     * Execute the console command.
     */
    public function handle()
    {
       $this->info('Ejecutando comando');
       \Log::info('Ejecutando comando'); 
       
       try {
            $inWallet = 5;
            $service = new TaikoChain();
            $contract = $service->getTokenContract('weth');
            $owner = config('blockchain.wallet');
            $wrapped = $service->getBalance($owner['address'], $contract);
            $this->info("Balance en token disponible: ".$wrapped);
            \Log::info("Balance en token disponible: ".$wrapped);
            if($wrapped == 0)
            {
                $this->info("Ejecutando metodo Deposit");
                \Log::info("Ejecutando metodo Deposit");
                $balance = $service->getBalance($owner['address']);
                $toDeduce = $balance - ($inWallet * $balance) / 100; 
                $porRandom = rand(1, 15);
                $amount = $toDeduce - ($porRandom * $toDeduce) / 100; 
                $this->info("A depositar: ".$amount);
                \Log::info("A depositar: ".$amount);
                $hash = $service->callContract($owner,$contract ,'deposit', 'weth', $amount );
            }else{
                $this->info("Ejecutando metodo Withdraw");
                \Log::info("Ejecutando metodo Withdraw");
                $total = $wrapped - (1 * $wrapped) / 100; 
                $this->info("A retirar: ".$total);
                \Log::info("A retirar: ".$total);
                $hash = $service->callContract($owner,$contract ,'withdraw', 'weth', $total );
            }

            $this->info("Hash de la transaccion: ".$hash);
            \Log::info("Hash de la transaccion: ".$hash);
       } catch (\Throwable $th) {;
           $this->fail($th->getMessage());
           \Log::error($th->getMessage());
        }
        
        $this->info('The command was successful!');
        \Log::info('The command was successful!');
    }
}
