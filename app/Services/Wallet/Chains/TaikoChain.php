<?php

namespace App\Services\Wallet\Chains;

use App\Services\Wallet\ChainInterface;
use App\Services\WalletService;
use Web3\Eth;
use Web3\Contract;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;
use App\Traits\ChainTrait;

class TaikoChain implements ChainInterface
{
	use ChainTrait;

	private $abiData;
	public function __construct()
	{
		$this->chainName = 'Taiko';
		$this->currency  = 'TAIKO';
		$this->abiData = file_get_contents(__DIR__ . '/abi/erc20.json');
		$this->setEnv();
		$rpc = $this->getChainConfig();
		$this->client = new Eth($rpc);
	}

	public function generateWallet(): array
	{
		return [];
	}

	public function getTransactionByHash(string $hash, &$transaction, $isContract = false)
	{

		$attempt = 0;
		$maxAttempts = 5;
		$delayInSeconds = 30;

		while ($attempt < $maxAttempts) {

			if ($isContract) {
				$this->client->getTransactionReceipt($hash, function ($err, $trx) use (&$transaction) {
					if ($err !== null) {
						throw new \Exception('Error: ' . $err->getMessage(), 1);
					}

					$transaction = $trx;
				});
			} else {

				$this->client->getTransactionByHash($hash, function ($err, $trx) use (&$transaction) {
					if ($err !== null) {
						throw new \Exception('Error: ' . $err->getMessage(), 1);
					}

					$transaction = $trx;
				});
			}

			if (!empty($transaction)) {
				return $transaction;
			}

			$attempt++;

			if ($attempt < $maxAttempts) {
				sleep($delayInSeconds);
			}
		}

		return [];
	}

	public function getAccount(string $address)
	{
	}

	public function getTransactionByHashPlain(string $hash, bool $isContract)
	{

		$transaction = null;
		if ($isContract) {
			$this->client->getTransactionReceipt($hash, function ($err, $trx) use (&$transaction) {
				if ($err !== null) {
					throw new \Exception('Error: ' . $err->getMessage(), 1);
				}

				$transaction = $trx;
			});
		} else {

			$this->client->getTransactionByHash($hash, function ($err, $trx) use (&$transaction) {
				if ($err !== null) {
					throw new \Exception('Error: ' . $err->getMessage(), 1);
				}

				$transaction = $trx;
			});
		}

		return $transaction;
	}
	public function getBalance(string $address, string $contract = '')
	{
		$total = 0;
		if ($contract != '') {
			$Icontract = new Contract($this->client->provider, $this->abiData);
			$Icontract->at($contract)->call('balanceOf', $address, function ($err, $result) use (&$total) {
				if ($err !== null) {
					throw new \Exception('Error: ' . $err->getMessage(), 1);
				}
				
				$total = gmp_strval($result[0]->value);
			});
		} else {

			$this->client->getBalance($address, function ($err, $balance) use (&$total) {
				if ($err !== null) {
					throw new \Exception('Error: ' . $err->getMessage(), 1);
				}
				$total = $balance->toString();
			});
		}

		if(strlen($total) <= 14)
		{
			return 0;
		}
		return floatval(WalletService::weiToEther($total));
	}

	public function sendtx(array $from, string $to, $amount, $contract)
	{
		$gasPrice = null;
		$estimateGas = null;
		$result = null;
		$value = Utils::toWei((string)$amount, 'ether');
		$nonce = 0;
		$chainId = ($this->env == 'mainnet') ? 167000 : 167009;
		$this->client->gasPrice(function ($err, $resp) use (&$gasPrice) {
			if ($err !== null) {
				throw new \Exception($err->getMessage());
			}
			$gasPrice = $resp;
		});

		$this->client->getTransactionCount($from['address'], function ($err, $resp) use (&$nonce) {
			if ($err !== null) {
				throw new \Exception($err->getMessage());
			}
			$nonce = $resp->toHex();
		});

		if(is_null($gasPrice) || is_null($nonce) || is_null($value)){
			throw new \Exception('El gasPrice, nonce o value no fueron retornados', 1);
		}

		if ($contract != '') {
			$Icontract = new Contract($this->client->provider, $this->abiData);
			$data = $Icontract->getData('transfer', $to, $value);
			$params = [
				'from' => $from['address'],
				'to' => $contract,
				'gas' => sprintf('0x%s', $gasPrice->toHex()),
			];

			$Icontract->at($contract)->estimateGas('transfer', $to, $value, $params, function ($err, $gas) use (&$estimateGas) {
				if ($err !== null) {
					throw new \Exception($err->getMessage());
				}

				$estimateGas = $gas;
			});

			if(is_null($estimateGas)){
				throw new \Exception('El gas estimado del envio del token no fue retornado', 1);
			}

			$transaction = new Transaction([
				'nonce' => sprintf('0x%s', $nonce),
				'from' => $from['address'],
				'to' => $contract,
				'gas' => sprintf('0x%s', $estimateGas->toHex()),
				'gasPrice' => sprintf('0x%s', $gasPrice->toHex()),
				'gasLimit' => sprintf('0x%s', 'D6D8'),
				'value' => '0x0',
				'chainId' => $chainId,
				'data' => sprintf('0x%s', $data),
			]);

			$signedTx = $transaction->sign($from['priv_key']);
			$result   = null;

			$this->client->sendRawTransaction(sprintf('0x%s', $signedTx), function ($err, $tx) use (&$result) {
				if ($err !== null) {
					throw new \Exception('Error en la transaccion de envio de token '.$err->getMessage(), 0, $err);
				}

				$result = $tx;
			});

			if (is_null($result)) {
				throw new \Exception('El resultado de la transacción de envio del token fue nula', 1);
			}

		} else {
			$this->client->estimateGas([
				'from' => $from['address'],
				'to' => $to,
				'value' => sprintf('0x%s', $value->toHex()),
			], function ($err, $resp) use (&$estimateGas) {
				if ($err !== null) {
					throw new \Exception($err->getMessage());
				}
				$estimateGas = $resp;
			});

			if(is_null($estimateGas)){
				throw new \Exception('El gas estimado del envio nativo (BNB) no fue retornado', 1);
			}

			$transaction = new Transaction([
				'nonce' => sprintf('0x%s', $nonce),
				'from' => $from['address'],
				'to' => $to,
				'gas' => sprintf('0x%s', $estimateGas->toHex()),
				'gasPrice' => sprintf('0x%s', $gasPrice->toHex()),
				'gasLimit' => sprintf('0x%s', 'D6D8'),
				'value' => sprintf('0x%s', $value->toHex()),
				'chainId' => $chainId,
			]);

			//dd($transaction->getTxData());
			$signedTx = $transaction->sign($from['priv_key']);
			$this->client->sendRawTransaction(sprintf('0x%s', $signedTx), function ($err, $tx) use (&$result) {
				if ($err !== null) {
					throw new \Exception($err->getMessage());
				}

				$result = $tx;
			});

			if (is_null($result)) {
				throw new \Exception('El resultado de la transacción de envio de BNB fue nula', 1);
			}
		}

		$contract = ($contract == '') ? false : true;
		return $this->checkConfirmations($result, $contract);
	}

	public function callContract(array $from, string $contract, string $method, string $abiname, string $amount)
	{
		$abiData =  file_get_contents(__DIR__ . '/abi/'.$abiname.'.json');
		$gasPrice = null;
		$estimateGas = null;
		$result = null;
		$value = Utils::toWei((string)$amount, 'ether');
		$nonce = 0;
		$chainId = ($this->env == 'mainnet') ? 167000 : 167009;
		$this->client->gasPrice(function ($err, $resp) use (&$gasPrice) {
			if ($err !== null) {
				throw new \Exception($err->getMessage());
			}
			$gasPrice = $resp;
		});

		$this->client->getTransactionCount($from['address'], function ($err, $resp) use (&$nonce) {
			if ($err !== null) {
				throw new \Exception($err->getMessage());
			}
			$nonce = $resp->toHex();
		});

			if(is_null($gasPrice) || is_null($nonce) || is_null($value)){
				throw new \Exception('El gasPrice, nonce o value no fueron retornados', 1);
			}


			$Icontract = new Contract($this->client->provider, $abiData);
			if($method == 'deposit')
			{
				$data = $Icontract->getData($method);
				$params = [
					'nonce' => sprintf('0x%s', $nonce),
					'from' => $from['address'],
					'to' => $contract,
					'data' => sprintf('0x%s', $data),
				];

				$Icontract->eth->estimateGas($params, function ($err, $gas) use (&$estimateGas) {

					if ($err !== null) {
						throw new \Exception($err->getMessage());
					}
	
					$estimateGas = $gas;
				});
				$hexVal =  sprintf('0x%s', $value->toHex());
			}else{
				$data = $Icontract->getData($method, $value);
				$params = [
					'nonce' => sprintf('0x%s', $nonce),
					'from' => $from['address'],
					'to' => $contract,
					'data' => sprintf('0x%s', $data),
				];
				
				$Icontract->at($contract)->estimateGas($method, sprintf('0x%s', $value->toHex()) ,$params, function ($err, $gas) use (&$estimateGas) {

					if ($err !== null) {
						throw new \Exception($err->getMessage());
					}
					$estimateGas = $gas;
				});
				//$hexVal =  sprintf('0x%s', $value->toHex());
				$hexVal = "0x0";
			}

			if(is_null($estimateGas))
			{
				$estimateGas = Utils::toBn(50000);
			}

			$tx = [
				'nonce' => sprintf('0x%s', $nonce),
				'from' => $from['address'],
				'to' => $contract,
				'gas' => sprintf('0x%s', $estimateGas->toHex()),
				'gasPrice' => sprintf('0x%s', $gasPrice->toHex()),
			    'gasLimit' => sprintf('0x%s', 'D6D8'),
				'value' => $hexVal,
				'chainId' => $chainId,
				'data' => sprintf('0x%s', $data),
			]; 

			$transaction = new Transaction($tx);
			$signedTx = $transaction->sign($from['priv_key']);
			$result   = null;
			//dd($transaction->getTxData());
			$this->client->sendRawTransaction(sprintf('0x%s', $signedTx), function ($err, $tx) use (&$result) {
				if ($err !== null) {
					throw new \Exception('Error en la transaccion de envio de token '.$err->getMessage(), 0, $err);
				}

				$result = $tx;
			});

			if (is_null($result)) {
				throw new \Exception('El resultado de la transacción de envio del token fue nula', 1);
			}
			return $result;
	}
}
