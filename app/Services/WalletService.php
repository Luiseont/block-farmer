<?php

namespace App\Services;

use GuzzleHttp\Client;
use Web3\Utils;


class WalletService
{

	private $client;
	private $chainName;
	public function __construct(String $chain)
	{
		try {
			$this->chainName = $chain;
			$chainName = ucwords(strtolower($chain)) . 'Chain';
			$className = 'App\Services\Wallet\Chains\\' . $chainName;
			if (
				class_exists($className)
				&& is_subclass_of($className, 'App\Services\Wallet\ChainInterface')
			) {
				$this->client = new $className();
			} else {
				throw new \Exception("Payment Method processor not active", 1);
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), 1);
		}
	}

	public function generateAddress()
	{
		return $this->client->generateWallet();
	}

	public function getTokenContract($name)
	{
		return $this->client->getTokenContract($name);
	}

	public function getTransactionByHash(string $hash, $isContract)
	{
		$transaction = [];
		return $this->client->getTransactionByHash($hash, $transaction, $isContract);
	}

	public function getAccount(string $address)
	{
		return $this->client->getAccount($address);
	}
	
	public function getClientEnv()
	{
		return $this->client->env;
	}

	public function getClientChainName()
	{
		return $this->client->chainName;
	}


	/**
	 *  crytocurrency is the tricker of crypto.. example BTC
	 *  Against pair example USDT
	 */
	public static function getPrice(string $crytocurrency, string $pair = "USDT"): String
	{
		$symbol = $crytocurrency . $pair;
		$client = new Client();
		$response = $client->get("https://data-api.binance.vision/api/v3/ticker/price?symbol=$symbol");

		if ($response->getStatusCode() === 200) {
			$data = json_decode($response->getBody(), true);
		} else {
			throw new \Exception("Error getting crypto currency rate", 1);
		}

		return $data['price'];
	}

	public static function hexToNumber($hexNumber)
	{
		if (!Utils::isZeroPrefixed($hexNumber)) {
			$hexNumber = '0x' . $hexNumber;
		}
		return intval(Utils::toBn($hexNumber)->toString());
	}

	public static function weiToEther($wei)
	{
		return bcdiv($wei, "1000000000000000000", 18);
	}

	public static function convertToBTCFromSatoshi($value)
	{
		return bcdiv(intval($value), 100000000, 8);
	}

	public static function removeZeros($address)
	{
		$non_zero_address = "";
		for ($i = 0; $i < strlen($address); $i++) {
			if ($address[$i] != "0") {
				$non_zero_address .= substr($address, $i);
				break;
			}
		}

		$length = strlen($non_zero_address);
		if ($length < 40) {
			$zeros = str_repeat('0', 40 - $length);
			$non_zero_address = $zeros . $non_zero_address;
		}
		return $non_zero_address;
	}

	public static function convert_hex_string($hex_string)
	{

		if (strlen($hex_string) !== 64) {
			throw new \ValueError("Input string must be 64 characters long");
		}

		// Remove the "0x" prefix (if present)
		$hex_string = ltrim($hex_string, "0x");

		// Ensure the string only contains valid hexadecimal characters
		if (!ctype_xdigit($hex_string)) {
			throw new \ValueError("Invalid hexadecimal string");
		}

		// Extract the first 42 characters
		return substr($hex_string, 0, 42);
	}

	public static function cleanTronAddress($address)
	{
		$with0x = false;

		// Remover el prefijo '0x' si está presente
		if (substr($address, 0, 2) === '0x') {
			$address = substr($address, 2);
			$with0x = true;
		}

		// Remover el prefijo '41' que es específico de Tron si está presente
		if (substr($address, 0, 2) === '41') {
			$address = substr($address, 2);
		}

		// Agregar el prefijo '0x' si estaba presente
		if ($with0x) {
			$address = '0x' . $address;
		}

		return $address;
	}

	public static function tronAddressToHexWith0x($value)
	{
		$value = self::cleanTronAddress($value);
		return '0x' . $value;
	}

	public static function hexToBin($value)
	{
		if (Utils::isZeroPrefixed($value)) {
			$count = 1;
			$value = str_replace('0x', '', $value, $count);
			$value = self::removeZeros($value);
			// avoid suffix 0
			if (strlen($value) % 2 > 0) {
				$value = '0x' . $value;
			}
		}

		return $value;
	}

	public static function removeXPrefix($value)
	{
		$count = 2;
		$value = str_replace('0x', '', $value, $count);
		$value = self::removeZeros($value);
		return $value;
	}

	public static function toParametersTronTriggerContract($value)
	{
		$count = 2;
		$value = str_replace('0x', '', $value, $count);
		$value = self::removeZeros($value);
		$value = str_pad($value, 64, "0", STR_PAD_LEFT);
		return $value;
	}
}
