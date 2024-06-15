<?php
namespace App\Services\Wallet;
interface ChainInterface{
	public function generateWallet(): Array;
	public function getTransactionByHash(string $hash, &$transaction, $isContract);
	public function getAccount(string $address);
	public function getTransactionByHashPlain(string $hash, bool $isContract);
	public function getBalance(string $address, string $contract);
	public function sendtx(array $from, string $to, string $amoun, string $contract);
	public function callContract(array $owner, string $contract, string $method, string $abiname, string $params);
}