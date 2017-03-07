<?php

require __DIR__ . "/../vendor/autoload.php";

try {

	$logger = new Monolog\Logger('BTCBridge');
	$logfilename = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "c:/ProgramData" : '/tmp/btcbridge.log';
	$logger->pushHandler(new Monolog\Handler\StreamHandler($logfilename),Monolog\Logger::DEBUG);
	$bridge = new \BTCBridge\Bridge(
		[
			//new \BTCBridge\Handler\MatbeaHandler()
			new \BTCBridge\Handler\BlockCypherHandler()
		]
		,new \BTCBridge\ConflictHandler\DefaultConflictHandler()
		,$logger
	);
	$res = $bridge->listtransactions("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy",["before"=>10,"after"=>20]);
	$res = $bridge->gettransaction("721dca6852f828af1057d5bf5f324a6d2b27328a27882229048cf340c1e3ec10");
	$res = $bridge->getbalance("deadka",4000);
	$res = $bridge->getunconfirmedbalance("deadka");
	//$res = $bridge->listunspent("deadka");
	$res = $bridge->listunspent("deadka"/*"12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"*/,0);
	$res = $bridge->sendrawtransaction("0100000001c94a679002e334674ad4d4e56deaaf3c6e7df1700c11812f319342c59641b815010000006b483045022100df9befbf00083719716e03310bceed664e7810b27eac884559f6dc6a4fe05dd7022060d4126d70ff399a9f90ad2352e837497163aacf50d61f423c2b8924bb537aec01210267af6c6bf4ae6e37f019fbfbc7df70acf48663adbf19161bd874f3babd6bf15c00000000027c150000000000001976a914df5d6e3c76eb3f38744fbe3b4a4f32aaaf7d607088ac5c951200000000001976a9140fb50d2ec6bb62bd690bb55142101ca28a678be188ac00000000");
} catch ( \InvalidArgumentException $ex) {
	fwrite(STDERR, $ex->getMessage() . PHP_EOL);
	exit(1);
} catch ( \RuntimeException $ex) {
	fwrite(STDERR, $ex->getMessage() . PHP_EOL);
	exit(1);
} catch ( \BTCBridge\Exception\ConflictHandlerException $ex ) {
	fwrite(STDERR, $ex->getMessage() . PHP_EOL);
	exit(1);
} catch ( \Exception $ex ) {
	fwrite(STDERR, $ex->getMessage() . PHP_EOL);
	exit(1);
}