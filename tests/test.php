<?php

require __DIR__ . "/../vendor/autoload.php";

$txs = [
    '00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295',
    '000000329877c7141c6e50b04ed714a860abcb15135611f6ac92609cb392ef60',
    '0000003d2bd90849b084e0d04afb33751a6187da86c424a6ed4b3ae5faf35313',
    '0000004a3ab8ada24ddf88c0cb94ea27d2d962db8c0aeeb35c5759342d326ef0',
    '0000004d6a850c95fcd3accdb2007542ad469b60eba76bf2c206d4cc563d93b0',
    '0000005d77a9a24d61889e9987b633ed5696f3a07c655eced8bebb147242abde',
    '0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655',
    '0000006f870414ecfc375dc6b5e51f0daad0bfb867cb49c0e63350ceac2c68a9',
    '0000007118a4d0a8eaaf31e9176f878e8f72dca5d58da1b2e67e7810c33dc9f6',
    '0000007ba8609f0acc2be539d628c246fddf945abf34c4af2028d4e60658bbb4',
    '0000008a1019e5c847530586d3282e8da6f1f87a26330c822f47a89e150cf503',
    '0000008b9f06ed2aa550b7e2efdd0abba73d619b917a77862b1954712075644d',
    '000000ad183cbae30594e0f06e90c57f3eee89f5a5aaac5170fa1ecc105a4f86',
    '000000d603e00e2d7dd3f53c02454719dc37fd5f47dc02a4a8b6f574e12f8bbd',
    '000000f870e3bbe4dc89519908c5160f91bcaf92bce66aeb7c3cce820eb357a6',
    '000001161e80043b3f2c9335eafd2666694ddcfb5236c653c60017479490b9be',
    '0000013066c9aaad31fb8eb52207779129b5881b6ba2b6d596d38f8746e5318e',
    '00000131c44a031d59e7248c256876271700d3ae268e32fd6348b30d92de1869',
    '0000017e95b848f84dc4122b97696c0d42368e3ffc65a0b845647027e6cbaa6e',
    '0000018c2f1887331db58050af9890a666649ad21b4536553de0c9b997fe3a31',
    '000001a632f6baa2cedb5b7d193bea9e76a0abb02da59fb7abd8179ea02a7b75',
    '000001b2c847b1aa29ef5f332509ff038788ab2eec566767266f54d4d3d8287d',
    '000001c631e8f30052e27414c69c9cf10eb0a0720c5282670efbef6529f69e35',
    '000001c805ea20e83919bede0c5170604d2e681c54da6fd0074b478e072681b4',
    '000001ccbe55f2467c74b986a10c584a7916b572187d62e90003b338f054ec20',
    '000001f7d15b50afe94597efea5e7f1a7a33a238e0ac0e10ce976ae88db01dde',
    '0000021838dec177b6cd38833aa52d5cee9e6cb183c6ed47b91837be300e41bf',
    '0000021b96f71321c68014e1611bc7c9919846dd16fd09801290b75c061fe0f7',
    '0000021e4b67431b13ab829bca850957b10a5cffa08e0bca5705cc20930a5478',
    '0000022287999d1af5028ee66d16c73e21e144edf62ab87287c3ee9789b8ff7b',
    '000002279c93d79a9c978fd31e8c185b03f5f247c524d0e146b9ff639a7e63c6',
    '0000022bec91129e7fdb9eb5e5867c75a7a1d365365d6eefb3abd94392f1a513',
    '00000233ab28ec14bb466f577219168ffde677ea07402b7808ba5e98eb035e0d',
    '00000246bb036fe76eaf0989230bd93d7e8c201f65f71fc46c920e90a55e2ae4',
    '0000024769291ed734fe2aece98da80dfe58fbd8010bf310f541ee77541d3473',
    '000002482829255c49452088f2fa73d15a1ebc7921eb4b196b720c0641dd6941',
    '0000024d8cec2a6e29b05231eb5b90f9105ad129b2f135c834b343ec87a36846',
    '0000027299a6459906df392269620213009ba3dac30fc62b16c26e5e6e1561bf',
    '00000286d55d3d4612346ff00a6358f790aa0b05e0e4f138485e3ff7a13da3aa',
    '000002aa1ac0ba088cb31a7ea9e1d625c9164360ccb4a40889c74567a9f44f6c',
    '000002cedb1e3337733745f23e4c5ccdc31867aaa7da4895fd047782d8808bab',
    '000002f3d047fc680a326c21fd7d057394c663301ed0992f704062a215f759e0',
    '000002fc671979702b94f4372d272bae152a6bd89d1831392ac6a11265d31f31',
    '000003048e10305ab2867321a7e818e8653af60fd5628989c67fec9cc73f44e9',
    '0000030fb4a9a7b9ad1bd5112eacde04a306f3f5e268098c9e8f1f813bb461e9',
    '0000031c3cd1d66cbd061d8156e6589944959799d2e1952dcec7500b4dda354b',
    '0000032275696c2fb8f93e5b9d22be352d984a31985475d1e74736c378e8683f',
    '00000334480293ea81971278e62917e5dd0f738839617206ddb3fe36de170d76',
    '0000033826c8c4ddffdfcb9ded4a9b418857c4d5cc1efcfd456b2f9d0226b9fd',
    '0000033e9ff3d8606f1708cca65fc6bda1f56ace5513003bc415ededb9afe311',
    '0000034d1fe0d48337c48ff4e43d7efee08b66309e0586075b0a204f8546c9a5',
    '0000035e2b0c54c24b3066fa3d56a9edc2dcfc0f6a62ac01e39233d2baadf99b',
    '0000036ae8fbfa786365f89bc1e5709101f69138747638550bd041d6a1ffc55b',
    '0000036dafc4ca9b4fa4f729937dd1416de081b695d706f3d17d856817b12e47',
    '000003afb4038d43fc7457b91028de52ff7d1864a7ae6add3a5a86f0fab5d578',
    '000003cdca1e1e75f3f8d408308440a5b1237864615ef1da9a749f29c5a0e991',
    '000003ec9c70cb3a0343da0798ce2fcfbf321cf65d880be4b48e6a38bc074005',
    '000003f4245471a4fd4f5e9eed5db600c4ef1c6b8de9e11d82d41aa5ea71ef16',
    '000003f8fd200bba6acc3dbe5610fa0d0bd868d1130910b06a4b14c55cd07d16',
    '000003fd6f35cd64a028eb49d1c9b593379fafcf4b9ced8c389257073f0dcafb',
    '0000040a85343b7fea0c21ce2b76dc4efa911555f73548437bc59db672392a49',
    '000004102645310943cdefc0e67b33d81e4120fe89b37fe1e91d23d2c2b37643',
    '0000041d8869dd940d8a62f99014136c2741d68f9a05714ac5b1b2c15a1f4be6',
    '00000430d3e8a9e16a644ad651fa926adf657190e246ed0cd20b72f50fd5fa26',
    '0000047d29b685768b4de68f0081f3cf1584b49642cd7cfcd0e58395db7861a5',
    '0000048020e8ab839923552315ad2bded92e451a6bdf0d1f2e8591284bd60260',
    '0000049a0e4d2fc1cd959e4c13385bd2cffaca3f2ca3c677d37f57dc02e7f335',
    '000004aaa7aacdd062c2ee56ae30c2cfc6b5245c931605c35b5a3857eef9aa4f',
    '000004ae99590851e847e653e277809fd153807ac3dbcb15737c80f5d20f3198',
    '000004b367a27999319c0ceded65efb7f46b1b24d98774a7fdb4f835af7aa281',
    '000004e2ff337bafd727fd3f2123d67618f0cbea5ce5ca7a180e7023fed54bcb',
    '000004e3a954d2342b7cf823b07cf08d401b4fef2119ecdbd1a0c4349f681c74',
    '00000501c49c634f79657f1a2e5952eb465ab4667b17928f66adc97e0cc15ad1',
    '0000050ab106f2d96de83aff7855cf9382164f6c306e7d14b2678c4d07bfcd28',
    '0000051af8b9bcb2b69c4afdc3fdfcf58be92929a5724ef4696e2a7adf659b70',
    '0000053dc6b53f1e8df19ce9848c10803b690aa5d69fea04ddf7bff4f7246ee4',
    '00000565ee1c58eb141e13c223e3d464138ac546c31da278398df589362976da',
    '000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb',
    '0000057889708cb169f9c7fd6fa6084dacd6345319b2c5c12cb2296c54f48086',
    '0000057dc0817fc7523ac2f54e4bfa865ecaf050457365bda6cfafdcc2fcb1ba',
    '00000594962a4359ffefa5a9f52e5f36a3de4f6983ea73f4331ac80cebb37e04',
    '000005aa852ecb214ef9fd21c4d4ff516b68263d6de64c7968e8e2551d6f0443',
    '000005b9581ac2fc850f010ad094c326b11b9a1906ac8f9fcca5fec6a62c34fd',
    '000005c5a86e5a705733991f131a9b589cc018652a4dcd2995420677201d7aba',
    '000005cf48cb98a5820f102f8e6bfcd51b3d25b058d7f061ba11dc7dc03414f3',
    '000005d1c4d9843a002b51c6cf4bc273bc2d7fdbbb338bbfccb9478346b96f0f',
    '000005d82b4265a7b9f52fc9a747150dfa53bbbf83fb070685ed2b5a9a1f300e',
    '000005fbf9a7ba80cefb87ccdea3f284b8ebf61722f1e749216306301290d536',
    '000006045caef5405cb359333ba4d469b05561bed4c7cc1b97f6dd4354bc36a1',
    '000006076e2723b160dfd3a3cd61d6d1297530a9bb8493c220ab90d3fb4c2eeb',
    '0000061e05f34132962d1de816ebcaf5123e0b651cbcb63d64de7a4154a1d569',
    '0000061f80295176a8bbda03c3112d2c21b74adaa1189a0cc850a710af64be68',
    '000006337cb3a238127976b8f20cd4e480e24d4457870a09d7b9fea97226e934',
    '000006960a39d5d55f1dfe41c3edb7186e66b81f38dfec93930daa46bf8561d1',
    '0000069d201db550375dfab21719c23e064f57a78ea874c8c7bb4f4f1b14aacb',
    '000006b5207da0c9d3b1599e1ee9f97a1c446b7dc007e301d40274669ac6c3fa',
    '000006b59217545730ca4d009f4f3e8385ebef851f3c4dacedc67ba2866d8aae',
    '000006c36a0e8e0b44467bc190182751c0982bec9f92ffad33315abce47e76e2',
    '000006e502f83a00677b7acb800ee6df08d5f78408ccd3c1036099e4e460ad5e',
    '000006e57ce90db7c9cfc3ad45a1a3cea4617626ff666ea289103a2b82aae27a'
];

try {
    $logger = new Monolog\Logger('BTCBridge');
    $logfilename = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "c:/ProgramData/btcbridge.log" : '/tmp/btcbridge.log';
    $logger->pushHandler(new Monolog\Handler\StreamHandler($logfilename));
    $blockTrailHandler = (new \BTCBridge\Handler\BlockTrailHandler())
        ->setToken("11ea0d1712216a4b8ef5da969111178459e5d02f");
    $matbeaHandler = (new \BTCBridge\Handler\MatbeaHandler())
        ->setToken("bvdb0uqv3ukr93ks9iis07so5639sit5");
    //$blockCypherHandler = (new \BTCBridge\Handler\BlockCypherHandler())->setToken("dc20a175f3594965a8f4707cdcf58a32");

    $bridge = new \BTCBridge\Bridge(
        [
            $matbeaHandler
            ,
            $blockTrailHandler
            //,
            //$blockCypherHandler
            //,
        ],
        new \BTCBridge\ConflictHandler\DefaultConflictHandler(),
        new \BTCBridge\ResultHandler\DefaultResultHandler(),
        $logger
    );

    $bridge->setOption(\BTCBridge\Bridge::OPT_LOCAL_PATH_OF_WALLET_DATA, __DIR__ . "/data/wallet.dat");

    //$res = $bridge->listunspent("19kBcrYnS1VAgEsvVpHf4Ki4LREUxJC4t2",0);

    //$res = $bridge->gettransactions(["0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098"]);
    //$v = $res[0]->getInputs()[0]->getOutputValue();
    //$val1 = $v->getGMPValue();
    //$val3 = $v->getBTCValue();
    //$val2 = $v->getSatoshiValue();
    //die;

    //$res = $bridge->gettransactions(["7890a6b1d38741a5b019e34c8576165195af9e7b9af7935024b150e870f530d2"]);
    //array_splice($txs, 20, 20);
    //$txs = ["82e406c824b89dd1b6aa1d49e6af6a7420d2bc30be1e3f783ddebeb9ee0da64e"];
    //$txs = ["d92a7814dd15f4380cd3f85e3efb24d887773eacecf0a604e23f0267eb70b48a"];
    //$txs = ["000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb"];
    //$txs = ["0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f"];
    //$txs = ["0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03"];
    /*$txs = [
        '000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f',
        '00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295',
        '0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03',
        '0000561d7d43a41a75a9ff78bba64f0d6dc3b1709aae58522f5f7eb11fec27a2',
        '000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb',
        '0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655'
    ];
    $txs = ["d01a99b5cf7b258d1431822b0fe151772023ca525269369ce6157bf6e32041cb"];
    $txs = ["0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f"];
    $txs = ["0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f"];
    $txs = ["0000fa31b80b4da025c65c32f06881e321d8eefcd949e0e96deb6e43b9bfc219"];*/

    //$res = $bridge->gettransactions(["000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb"]);
    //$res = $bridge->gettransactions(["2d05f0c9c3e1c226e63b5fac240137687544cf631cd616fd34fd188fc9020866"]);
    //die;

    //$chunks = array_chunk($txs, 20);
    //foreach ($chunks as $chunk) {
        //$res = $bridge->gettransactions($chunk);
    //}
    //die;
    //$res = $bridge->listtransactions("deadushka1");

    //$txs = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655","00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"],[]);
    /*$txs = [
        '0000354c3112e062f26df428ab831ff33ae2aca1381982931a33e40c778cbca2',
        '00004973ccacb026732e8751ef8e9dedd1706b1cf27d5308a5097fd98cca09e7',
        '0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f', //CRASH
        '0000bf2e205a47994c1a9a0525d1d1658216b036baa1fe6bc8d50417e1ac61a8',
        '0000e58684b78dfd555ade10a49d043d3a2d059940a3a8e2a2b6e15ddfe1153b',
        '0000fa31b80b4da025c65c32f06881e321d8eefcd949e0e96deb6e43b9bfc219', //CRASH
        '00010dea7f32ece989228fc2b8d3209aa165f74742bf14c5435edd3d27239b4e',
        '00011c29277fc948629be3f9c00fd26d11ec19b952402d1b0df7658fdb33ebeb'  //CRASH
    ];*/
    //$txs = array_slice($txs, 0, 3);
    //$txs = $bridge->gettransactions($txs,[]);
    //foreach ( $txs as $tx ) {
        //$r = $bridge->gettransactions([$tx],[]);
    //}
    //die;



    //$res = $bridge->getnewaddress();
    //$res = $bridge->dumpprivkey("1GC5nxT5cUASbqMcCkB94ZvH6C1pq6eADg");

    //$balance = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655","00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"],[]);

    //$res = $bridge->listunspent("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");

    //$res = $bridge->listunspent("stst");
    //$res = $bridge->listunspent("19LtvgLf6ciPVLNAFqfQ3bpbmz1h7nGbjE");
    //$res = $bridge->getbalance("19LtvgLf6ciPVLNAFqfQ3bpbmz1h7nGbjE");
    //$res = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655","00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"],[]);

    //$wallet = new \BTCBridge\Api\Wallet;
    //$wallet->setSystemDataByHandler($matbeaHandler->getHandlerName(), ["name"=>"matbea-com-test","id"=>700]);
    //$wallet->setName("matbea-com-test");
    //$res = $bridge->listtransactions($wallet->getName());
    //die;
    //$bridge->deleteWallet($wallet);
    //$wallet = $bridge->createWallet("matbea-com-test",['12S42ZEw2741DHrivgZHLfX8M58mxb7bFy','1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    /*$wallet = $bridge->createWallet("deadushka1",['1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    $addresses = $bridge->getAddresses($wallet);
    $wallet = $bridge->addAddresses($wallet,['1BdxBor4JG76RKLAwJZfHC58fWbgidYukz','1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    $addresses = $bridge->getAddresses($wallet);
    $wallet = $bridge->removeAddresses($wallet,['1BdxBor4JG76RKLAwJZfHC58fWbgidYukz','1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    $bridge->deleteWallet($wallet);
    die;*/




    /*$res = $bridge->gettransactions(["21df512a116abb22384bfe47f15833e43ac4f8999b434a7a5c74ad1f487043a9"]); //unconfirmed
    $res = $bridge->gettransactions(["000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f"]); //different time
    $res = $bridge->gettransactions(["a1405d6b266b63a2d1a5af6b3dee1af9ae60124be16f81b4774059c7dd43aa27"]); //newly minted OK
    $res = $bridge->gettransactions(["000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f"]); //newly minted OK
    $res = $bridge->gettransactions(["00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"]); //pubkeyhash
    $res = $bridge->gettransactions(["0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03"]); //multisig !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    $res = $bridge->gettransactions(["0000561d7d43a41a75a9ff78bba64f0d6dc3b1709aae58522f5f7eb11fec27a2"]); //nonstandard
    $res = $bridge->gettransactions(["000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb"]); //nulldata
    $res = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655"]); //scripthash
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>1]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>2]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>1,"outstart=1000"]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>2,"outstart=2000"]); //13106 outputs (tx_id=101172926)*/


    /*$wallet = new \BTCBridge\Api\Wallet();
    $wallet->setSystemDataByHandler($blockCypherHandler->getHandlerName(), ["name"=>"todo5"]);
    //$addresses = $bridge->getAddresses($wallet);
    $wallet->setSystemDataByHandler($matbeaHandler->getHandlerName(), ["id"=>"123","name"=>"todo5"]);
    $wallet = $bridge->createWallet("todo5", ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz","12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"]);
    $bridge->deleteWallet($wallet);

    //$wallet = $bridge->addaddresses($wallet, ["12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"]);
    //$wallet = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz;12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");
    $wallet = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");
    $wallet = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");

    $res = $bridge->sendrawtransaction("0100000001c94a679002e334674ad4d4e56deaaf3c6e7df1700c11812f319342c59641b8150".
    "10000006b483045022100df9befbf00083719716e03310bceed664e7810b27eac884559f6dc6a4fe05dd7022060d4126d70ff399a9f90a".
    "d2352e837497163aacf50d61f423c2b8924bb537aec01210267af6c6bf4ae6e37f019fbfbc7df70acf48663adbf19161bd874f3babd6bf".
    "15c00000000027c150000000000001976a914df5d6e3c76eb3f38744fbe3b4a4f32aaaf7d607088ac5c951200000000001976a9140fb50".
    "d2ec6bb62bd690bb55142101ca28a678be188ac00000000", 0);


    //$balance = $bridge->getbalance("1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");

    //$addr = $bridge->getnewaddress("sdf");
    //$wif = $bridge->dumpprivkey("sdf1", "1JTypCDWN7a7GNHaZdwjUkGQyFwhFqTuGL");

    //It will raise the exception , because % sybol is contained in the string
    //$res = $bridge->createWallet("deadka1-%_", []);

    //$wallet = new BTCBridge\Api\Wallet();
    //$wallet->setSystemDataByHandler($blockCypherHandler->getHandlerName(), ["name"=>"todo"]);
    //$wallet->setSystemDataByHandler($matbeaHandler->getHandlerName(), ["id"=>"123","name"=>"todo"]);
    //$bridge->deleteWallet($wallet);
    //$bridge->deleteWallet($wallet);
    //$wallet = $bridge->createWallet("todo", ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz"]);
    //$wallet = $bridge->createWallet("todo", ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz"]);
    //$wallet = $bridge->addaddresses($wallet, ["12S42ZEw2741DHrivgZHLfX8M58mxb7bFy"]);
    //$res = $bridge->removeAddress($wallet, "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");
    //$addresses = $res = $bridge->getAddresses($wallet);

    //$res = $bridge->listtransactions("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy", []);
    //$res = $bridge->gettransactions(["a1405d6b266b63a2d1a5af6b3dee1af9ae60124be16f81b4774059c7dd43aa27"]);
    //$balance = $bridge->getbalance("1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");
    //$unconfirmedbalance = $bridge->getunconfirmedbalance("1BdxBor4JG76RKLAwJZfHC58fWbgidYukz");

    $bridge->setOption(\BTCBridge\Bridge::OPT_MINIMAL_FEE_PER_KB, "163054");
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 1179178 - 41242 - 0);
    $sendMoneyOptions = new BTCBridge\Api\SendMoneyOptions();
    $sendMoneyOptions->setConfirmations(1);
    $sendMoneyOptions->setComment("");
    $sendMoneyOptions->setCommentTo("");
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 1179178 - 41242 - 0);

    //$smoutput = new \BTCBridge\Api\SMOutput();
    //$smoutput->setAddress("1BdxBor4JG76RKLAwJZfHC58fWbgidYukz")->setAmount(1179178 - 41242 - 0);
    //$res = $bridge->sendmanyEX("tst", [$smoutput], $sendMoneyOptions);
    //$res = $bridge->getunconfirmedbalance("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");
    //$res = $bridge->getbalance("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");



    $txs = [
        '0000354c3112e062f26df428ab831ff33ae2aca1381982931a33e40c778cbca2',
        '00004973ccacb026732e8751ef8e9dedd1706b1cf27d5308a5097fd98cca09e7',
        '0000a524025cca89db9743a6ec940d2a987bbb7f19f392adb3912b85c7a9a12f', //CRASH
        '0000bf2e205a47994c1a9a0525d1d1658216b036baa1fe6bc8d50417e1ac61a8',
        '0000e58684b78dfd555ade10a49d043d3a2d059940a3a8e2a2b6e15ddfe1153b',
        '0000fa31b80b4da025c65c32f06881e321d8eefcd949e0e96deb6e43b9bfc219', //CRASH
        '00010dea7f32ece989228fc2b8d3209aa165f74742bf14c5435edd3d27239b4e',
        '00011c29277fc948629be3f9c00fd26d11ec19b952402d1b0df7658fdb33ebeb'//CRASH
    ];
    foreach ($txs as $txhash) {
        $res = $bridge->gettransactions([$txhash]);
    }

    //$res = $bridge->gettransactions(["dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"],["limit"=>PHP_INT_MAX,"outstart=2000"]); //13106 outputs (tx_id=101172926)


    die;
    //$res = $bridge->addaddresses("tst", ["1BdxBor4JG76RKLAwJZfHC58fWbgidYukz"]);
    //$res = $bridge->getAddresses("tst");
    //$res = $bridge->listunspent("tst");

    //$res = $bridge->getAddresses("deadka");
    //$res = $bridge->getAddresses("tst");


    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 1202384);
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 5500 + 2530);
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 13894 - 2530);
    //$res = $bridge->sendfrom("tst", "1BdxBor4JG76RKLAwJZfHC58fWbgidYukz", 13894 - 2530 - 0);
    //$bridge->settxfee(70000);
    //$smoutput = new \BTCBridge\Api\SMOutput();
    //$smoutput->setAddress("1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7")->setAmount(5500);
    //$res = $bridge->sendmany("tst", [$smoutput]);



    //$res = $bridge->getAddresses("deadka");

    //$res = $bridge->sendrawtransaction("0100000001c94a679002e334674ad4d4e56deaaf3c6e7df1700c11812f319342c59641b8150".
    //"10000006b483045022100df9befbf00083719716e03310bceed664e7810b27eac884559f6dc6a4fe05dd7022060d4126d70ff399a9f90a".
    //"d2352e837497163aacf50d61f423c2b8924bb537aec01210267af6c6bf4ae6e37f019fbfbc7df70acf48663adbf19161bd874f3babd6bf".
    //"15c00000000027c150000000000001976a914df5d6e3c76eb3f38744fbe3b4a4f32aaaf7d607088ac5c951200000000001976a9140fb50".
    //"d2ec6bb62bd690bb55142101ca28a678be188ac00000000", 0);
    */
} catch (\InvalidArgumentException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\RuntimeException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\BTCBridge\Exception\HandlerErrorException $ex) {
    $successHandlers = $ex->getSuccessHandlers();
    $errorHandler = $ex->getErrorHandler();
    $unusedHandlers = $ex->getUnusedHandlers();
    $result = $ex->getResult();
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\BTCBridge\Exception\ConflictHandlerException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\BTCBridge\Exception\ResultHandlerException $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
} catch (\Exception $ex) {
    fwrite(STDERR, $ex->getMessage() . PHP_EOL);
    exit(1);
}
