<?php 

return [
	"wallet" => [
		'address' => env('W_ADDRESS',''),
		'priv_key' => env('W_PRIVATE',''),
	],
	'tron' => [
		'mainnet' => 'https://multi-wiser-snow.tron-mainnet.quiknode.pro/f2a6d01d2010d8d7d1975b454cf36772b8457a19/',
		'testnet'	=> 'https://nile.trongrid.io/'
	],
	'bnbchain' =>[
		'mainnet' => 'https://side-responsive-frost.bsc.quiknode.pro/8d4d8c168013ddd70c128214453cf1e3e219bd0a/',
		'testnet' => 'https://frosty-fragrant-owl.bsc-testnet.quiknode.pro/2950d4db534adb57e818cd262dcbf4c4cec897e3/'
	],
	'taiko' => [
		'mainnet' 	=> 'https://rpc.mainnet.taiko.xyz/',
		'testnet'	=> 'https://rpc.hekla.taiko.xyz/'
	],
	//contracts
	"weth" => [
		'mainnet' 	=> '0xA51894664A773981C6C112C43ce576f315d5b1B6',
		'testnet'	=> '0xae2C46ddb314B9Ba743C6dEE4878F151881333D9'
	]
];