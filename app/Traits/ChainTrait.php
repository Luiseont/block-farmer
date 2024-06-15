<?php
namespace App\Traits;

use App\Services\ConsolidateService;

trait ChainTrait
{
	protected $client;
	public 		$chainName;
	public 		$currency;
	public $env;

	public function getChainConfig(): string
	{
		return config('blockchain')[strtolower($this->chainName)][$this->env];
	}

	public function getTokenContract(string $name): string
	{
		return config('blockchain')[strtolower($name)][$this->env];
	}
	

	public function setEnv()
	{
		$this->env = "mainnet";
	}

	public function checkConfirmations(string $hash, bool $isContract = false)
	{
		$attempt = 0;
		$maxAttempts = 5;
		$delayInSeconds = 35;

		while ($attempt < $maxAttempts) 
		{
			try {
				$transaction = $this->getTransactionByHashPlain($hash, $isContract);
			} catch (\Throwable $th) {
				\Log::error('consolidate-balance: error getting transaction by hash: '.$hash.' in '.$this->chainName);
				$transaction = [];
			}
	

			 if (!empty($transaction)) {

				if($this->chainName == 'BnbChain' && !is_null($transaction->blockHash))
				{
					return [
						'blockHash' => $transaction->blockHash,
					];
				}

				if($this->chainName == 'Tron' && ((isset($transaction['ret']) && $transaction['ret'][0]['contractRet'] == 'SUCCESS') || isset($transaction['log'])))
				{
					return [
						'blockHash' => $hash,
					];
				}
			}


			$attempt++;
			info('consolidate-balance: esperando la transaccion pendiente con hash: '.$hash.' in '.$this->chainName);
			if ($attempt < $maxAttempts) {
				sleep($delayInSeconds);
			}
		}	

		throw new \Exception("Luego de 5 intentos no se pudo obtener la transaccion de confirmacion $hash", 1);
	}
}