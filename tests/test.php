<?php

/*$urls = array(
    'http://www.google.ru/',
    'http://www.altavista.com/',
    'http://www.yahoo.com/'
);
$mh = curl_multi_init();
foreach ($urls as $i => $url) {
    $conn[$i]=curl_init($url);
    curl_setopt($conn[$i],CURLOPT_RETURNTRANSFER,1);  //ничего в браузер не давать
    curl_setopt($conn[$i],CURLOPT_CONNECTTIMEOUT,10); //таймаут соединения
    curl_multi_add_handle ($mh,$conn[$i]);
}
//Пока все соединения не отработают
do { curl_multi_exec($mh,$active); } while ($active);
//разбор полетов
for ($i = 0; $i < count($urls); $i++) {
    //ответ сервера в переменную
    $res[$i] = curl_multi_getcontent($conn[$i]);
    curl_multi_remove_handle($mh, $conn[$i]);
    curl_close($conn[$i]);
}
curl_multi_close($mh);
print_r($res);
die;

$urls = [
    'https://api.blockcypher.com/v1/btc/main/txs/0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655' => ['handler' => 'empty1'],
    'https://api.blockcypher.com/v1/btc/main/txs/00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295' => ['handler' => 'empty2']
];

$multi = curl_multi_init();
$allResults = [];
foreach ($urls as $url => $urlData) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_multi_add_handle($multi, $ch);
    $urls[$url]['ch'] = $ch;
}

//$s = microtime(true);
$active = null;
do {
    $mrc = curl_multi_exec($multi, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
    # for php 7+
//            if (curl_multi_select($multi) == -1) {
//                continue;
//            }

    # For php 5.6
    if (curl_multi_select($multi,4) == -1) {
        //usleep(500);
        usleep(1000000);
    }

    do {
        $mrc = curl_multi_exec($multi, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
}
//echo 'SPEND '.(microtime(true) - $s)."\n";

foreach ($urls as $url => $urlData) {
//            echo "-----------------------------------".$url."\n\n";
//            echo curl_multi_getcontent($urlData['ch'])."\n";
    $content = curl_multi_getcontent($urlData['ch']);
    $allResults[] = json_decode($content, true);
    curl_multi_remove_handle($multi, $urlData['ch']);
}
curl_multi_close($multi);
*/

require __DIR__ . "/../vendor/autoload.php";

try {
    $logger = new Monolog\Logger('BTCBridge');
    $logfilename = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "c:/ProgramData/btcbridge.log" : '/tmp/btcbridge.log';
    $logger->pushHandler(new Monolog\Handler\StreamHandler($logfilename), Monolog\Logger::DEBUG);
    $blockCypherHandler = (new \BTCBridge\Handler\BlockCypherHandler())->setToken("dc20a175f3594965a8f4707cdcf58a32");
    $matbeaHandler = (new \BTCBridge\Handler\MatbeaHandler())->setToken("bvdb0uqv3ukr93ks9iis07so5639sit5");

    $bridge = new \BTCBridge\Bridge(
        [
            $blockCypherHandler
            ,
            $matbeaHandler
            //,
            //(new \BTCBridge\Handler\BlockCypherHandler())->setToken("dc20a175f3594965a8f4707cdcf58a32")
        ],
        new \BTCBridge\ConflictHandler\DefaultConflictHandler(),
        new \BTCBridge\ResultHandler\DefaultResultHandler(),
        $logger
    );

    $bridge->setOption(\BTCBridge\Bridge::OPT_LOCAL_PATH_OF_WALLET_DATA, __DIR__ . "/data/wallet.dat");


    //$res = $bridge->getnewaddress();
    //$res = $bridge->dumpprivkey("1GC5nxT5cUASbqMcCkB94ZvH6C1pq6eADg");

    //$balance = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655","00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"],[]);

    //curl -k "https://api.blockcypher.com/v1/btc/main/txs/00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295;21df512a116abb22384bfe47f15833e43ac4f8999b434a7a5c74ad1f487043a9?token=dc20a175f3594965a8f4707cdcf58a32" > 2
    //curl -k "https://api.blockcypher.com/v1/btc/main/txs/0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098;9b0fc92260312ce44e74ef369f5c66bbb85848f2eddd5a7a1cde251e54ccfdd5;999e1c837c76a1b7fbb7e57baf87b309960f5ffefbf2a9b95dd890602272f644;df2b060fa2e5e9c8ed5eaf6a45c13753ec8c63282b2688322eba40cd98ea067a;63522845d294ee9b0188ae5cac91bf389a0c3723f084ca1025e7d9cdfe481ce1;20251a76e64e920e58291a30d4b212939aae976baca40e70818ceaa596fb9d37;8aa673bc752f2851fd645d6a0a92917e967083007d9c1684f9423b100540673f;a6f7f1c0dad0f2eb6b13c4f33de664b1b0e9f22efad5994a6d5b6086d85e85e3;0437cd7f8525ceed2324359c2d0ba26006d92d856a9c20fa0241106ee5a597c9;d3ad39fa52a89997ac7381c95eeffeaf40b66af7a57e9eba144be0a175a12b11;f8325d8f7fa5d658ea143629288d0530d2710dc9193ddc067439de803c37066e;3b96bb7e197ef276b85131afd4a09c059cc368133a26ca04ebffb0ab4f75c8b8;9962d5c704ec27243364cbe9d384808feeac1c15c35ac790dffd1e929829b271;e1afd89295b68bc5247fe0ca2885dd4b8818d7ce430faa615067d7bab8640156;50748b7a193a0b23f1e9494b51131d2f954cc6cf4792bacc69d207d16002080d;e79fc1dad370e628614702f048edc8e98829cf8ea8f6615db19f992b1be92e44;a3e0b7558e67f5cadd4a3166912cbf6f930044124358ef3a9afd885ac391625d;f925f26deb2dc4696be8782ab7ad9493d04721b28ee69a09d7dfca51b863ca23;9b9e461221e5284f3bfe5656efdc8c7cc633b2f1beef54a86316bf2ae3a3e230;ee1afca2d1130676503a6db5d6a77075b2bf71382cfdf99231f89717b5257b5b;e0175970efb4417950921bfcba2a3a1e88c007c21232ff706009cc70b89210b4;9e2eaf1d7e5178a2d116f331c340f1f7fd6de7540783ac36a7054fe9a4d64943;e1cf3476234d8446653ad52a8939ed792003eefdcd0e897319ab9d2cb4c14c8c;43c39b8b3728c6cb26fae0fc18803ca6cf43e15fde218e3dfbd54e633cf6753e;9b0f52332d7d013b49016416c4818b5abb80b01ca526b7b813830348ad2321be;67c1e8143bb6ad221a4ce77d6c8be68f2e25e0743f51b2db1a7b22bab59014dc;230cf03a6ce420eaa42e3c64feebb47920f3470efb4323b4574b4b6e5a004f65;f399cb6c5bed7bb36d44f361c29dd5ecf12deba163470d8835b7ba0c4ed8aebd;c361e2f4581f035dd58b99788347884e046e47b4c17ec347344ff8b24cd377ec;f5e26c8b82401c585235c572ba8265f16f7d9304ed8e31c198eab571754f5331;86b33edba8ff663b0f73ef487e4433f34d26ef91de15659d2cc09594d27b52cb;2b9905f06583c01454f10f720b5709e3b667c9dd3d9efc423c97b7e70afdc0c9;bdeaa0089cd84670da5e6385f0185c2d7978bf57a1aa5540d3ff3b3eabaa1210;223b0620a8f1c1f23a2ebf8032ed11321b921017d01974e74cddd651319b5474;852b1997ed935ba638078998e2d15bc8a91b8ad232e2d988e22c969eba3bafe0;d05d256fbd5845b30039e37d48215788a4e438249048c47ddb9c83cd927d4d5a;6d344eb5d67ed329a1c1d7603bba4b85d5916435b49f7a585bb370b76820287d;e690daeb9f73d29d8a22cb4b5ec29970e9b32283d4376adeaad8691ccb449a68;1484c18ba443b13851098597d8cb6d49d5983eab63c53d6b0dcc565612e7ca6b;04391286b3aefbb5df4cdb515ac7fce7942525fa602e1d7757e90a4fd41a1e20;9efa6cb3b8cca3c9387144f397f80e7b4bc2dd86026fdd308625a2e100a08d5a;27c4d937dca276fb2b61e579902e8a876fd5b5abc17590410ced02d5a9f8e483;2f5c03ce19e9a855ac93087a1b68fe6592bcf4bd7cbb9c1ef264d886a785894e;439aee1e1aa6923ad61c1990459f88de1faa3e18b4ee125f99b94b82e1e0af5f;f69778085f1e78a1ea1cfcfe3b61ffb5c99870f5ae382e41ec43cf165d66a6d9;ddd4d06365155ab4caaaee552fb3d8643207bd06efe14f920698a6dd4eb22ffa;d17b9c9c609309049dfb9005edd7011f02d7875ca7dab6effddf4648bb70eff6;32edede0b7d0c37340a665de057f418df634452f6bb80dcb8a5ff0aeddf1158a;194c9715279d8626bc66f2b6552f2ae67b3df3a00b88553245b12bffffad5b59;540a7e54fd64478554519f1b2d643ecc888c5030631487f9cfc530b71d281309;a09c89d2a31440658a42ec97aeee0d36b01529e45b315922e2aa2955334d1821;b3f978e6ee5e91662af30a634b3f3268c6f5d34aac1eb54e6cc9535026f5084f;3c6f58905f06a9fe9b4313a88827c43dbfb0156fcc3b17bb311d51e8be6746f5;d3dc9be5d58809579e56cdd64e78257ecd24e2726cf26ef0a698f4e5c07670b5;19521c62482ac299b37c8ae4eab67abd150e3c21dc66b7e2b0919444769ee2ed;fe9ccc6c8b44c67ab170135d4148d6424e748f7549547d8174d59b5127df0102;bc0f0f8b3235421a036122299db7046a00c3a7cd1d650de3d92969780b873728;9635054e3de101ea39dcfa5f4cec63ceb1205db6e0a99304c8db2b3a162137e4;3c0db11484606a63b04ef4b8adcd665655939d3932ab1ebe90d1dfc5f077ca95;82a1e1731a9b22fdea55c09f2fac191a89efee127956fcfef65caab70f54002d;ae6eedf8e47ac6dda10ca9f3334bd3031795c55a124948003acd944ebd31fee1;701ce76c033e0b03fa79503770a5874840373e30cd9c1eca472ec66617f3a3ee;0731f33cf07a2e5f749a2910c437a015968e1fc2ed79c95634829167db280c4f;ebdb8335d5b148e9cc1b1bb795ee619a649bd1638e5514fdfc3004b1c56fd6a4;f15c7c51118c1dc93129a9f4999c7768ef2ff8cda3230f56d32576518c5f9349;f01d7897ca02ed20dfb5544b3c1228e225fd97b72510d40af6c9d8e489033a74;b17fc28c1dc15ca4b05bf4784419c145b14e79ece54c793656997984c4e46715;bdc5121446fd203abbf0e4d13fe99594dc3dc4c1cb95504feb459e8982d923be;807822a80b4ed7b7dfbe560a79705c62a71e48be9dbcce152ab70f94b1eb4307;aa5f3068b53941915d82be382f2b35711305ec7d454a34ca69f8897510db7ab8;fd83acf6e35be34a654ae53fbdc43ae4a7c4e1bcc334064a6360bf6b32339fcb;dea3224cc1333edf41b3b97913d8b2a6402bddf6132f2137e003b535369f9ae1;41b48c64cba68c21e0b7b37f589408823f112bb7cbccef4aece29df25347ffb4;d472546c5171ea73bc9000f2994f069631c7706f5e922e2fe5f8e50e0402bed6;382501ac2d50c5944465c2c316dbe2c70f23dd0de73ea86d339ea5f2bca7b648;47c981c4193d62d9d33b59fe1a6de9c27ff78d9f9003325b61e686a720673626;b5015619429af022efa69e0401891a92533b1852f214b68504c2e435a8cb7d0e;7ea1d2304f1f95fae773ed8ef67b51cfd5ab33ea8b6ab0a932ee3e248b7ba74c;fb9967077804b9a4b7f5c80f7d8c567639cc14b17955da27c10b9947ddbd1825;5ed287fa7b07229b53b15c6ad95ab49c1c222aa0fcfa2dd6603fa8492fae54c6;5c4c653395879f5992949b3e28bb0be7b27d49e9e11d64899c5de30928d4ad99;81fb4b1430e5c2cdee38be1a147c45fe23b38bbad0375ff67dc64450844445be;561eebc615218a12a9588ac78dde63aa56f3b1b43eb17174d2f093d1e2d1ca73;9a3d8485cde248ab7dcea68c5a8539498ecb8ad337f2574fc2143d2f644b2e1d;c39bde3039372f835ca96f15d5f38df585fac24a316d28cb5febae7c4af15cb4;12272d79ff1fbc1238d78d7d6e0e75c5f0e7aae7925e4f59b96b02fc2cd82bfc;edb7b78f88263e5c47717ee0761131683e85cc31beeac9a98554917c30686440;797556eb999f8fdfd8198dd9ea80723409a359ddcd75c4a54a511970745812f0;2306da842919574ed10ee9c211508b2bae2e8729b2fa5e83b4173831b89d2c28;9f94cc5c9211258c39f048f4da42dd3048e4d1434c2eff2d28dbf3b3612775c6;c184c93d7494f4096e9e9cb420f7d74c79c4ffd2806d68ff03bb7633bbf21d67;81b896b98663fb6238c9643545eba3a1b32933f96e1a33d122efed5ab7165f2d;71cbe112176d6dc40490dde588798bd75de80133438016a0c05754d74ee1925a;3226a75aaf23565ab1f4b4d3cf7f945e188b1ef35d14bba50bd3ee05df985490;8f2cd8d8c2b4ac9162cd8fb11856982ca1bb33898d19f6b50d8361b1318b7dc0;5f716bfbb5be57a4f2283509938898cffe7971af12adb152a058ac02405c85d8;cc4d0dd009ccf08805d9dca9232a47c60681375312a520f3609045649c2a1690;d0d31488af1205e17630a459d0e2874d18e3102bee42b4dd3806dd9169638b66;33a4592d4d8dc04304361ad7bcd96fe23fa801db6d7d917074f75c732079a569?token=dc20a175f3594965a8f4707cdcf58a32" > 2

    //$res = $bridge->listunspent("12S42ZEw2741DHrivgZHLfX8M58mxb7bFy");

    //$res = $bridge->listunspent("stst");
    //$res = $bridge->listunspent("19LtvgLf6ciPVLNAFqfQ3bpbmz1h7nGbjE");
    //$res = $bridge->getbalance("19LtvgLf6ciPVLNAFqfQ3bpbmz1h7nGbjE");
    //$res = $bridge->gettransactions(["0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655","00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"],[]);

    $wallet = new \BTCBridge\Api\Wallet();
    //$wallet->setSystemDataByHandler($blockCypherHandler->getHandlerName(), ["name"=>"deadushka"]);
    //$bridge->deleteWallet($wallet);
    $wallet = $bridge->createWallet("deadushka1",['1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    $addresses = $bridge->getAddresses($wallet);
    $wallet = $bridge->addAddresses($wallet,['1BdxBor4JG76RKLAwJZfHC58fWbgidYukz','1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    $addresses = $bridge->getAddresses($wallet);
    $wallet = $bridge->removeAddresses($wallet,['1BdxBor4JG76RKLAwJZfHC58fWbgidYukz','1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7']);
    $bridge->deleteWallet($wallet);
    die;

    //$res = $bridge->gettransaction("0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03"); //multisig !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


    /*$res = $bridge->gettransaction("21df512a116abb22384bfe47f15833e43ac4f8999b434a7a5c74ad1f487043a9"); //unconfirmed
    $res = $bridge->gettransaction("000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f"); //different time
    $res = $bridge->gettransaction("a1405d6b266b63a2d1a5af6b3dee1af9ae60124be16f81b4774059c7dd43aa27"); //newly minted OK
    $res = $bridge->gettransaction("000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f"); //newly minted OK
    $res = $bridge->gettransaction("00000005aca88ceece655e19070dbfe9416b0c2850da0463f1e4c823bb41f295"); //pubkeyhash
    $res = $bridge->gettransaction("0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03"); //multisig !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    $res = $bridge->gettransaction("0000561d7d43a41a75a9ff78bba64f0d6dc3b1709aae58522f5f7eb11fec27a2"); //nonstandard
    $res = $bridge->gettransaction("000005691f815e9de6977f8e596e383bc904528d6aa173d9a23656f6befbfacb"); //nulldata
    $res = $bridge->gettransaction("0000005f67276a9d277507f1439ff6c322d7e969b855e449aec6b34b0b6d1655"); //scripthash
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3"); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>1]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>2]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>1,"outstart=1000"]); //13106 outputs (tx_id=101172926)
    $res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>2,"outstart=2000"]); //13106 outputs (tx_id=101172926)*/


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
    //$res = $bridge->gettransaction("a1405d6b266b63a2d1a5af6b3dee1af9ae60124be16f81b4774059c7dd43aa27");
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
        $res = $bridge->gettransaction($txhash);
    }

    //$res = $bridge->gettransaction("dd9f6bbf80ab36b722ca95d93268667a3ea6938288e0d4cf0e7d2e28a7a91ab3",["limit"=>PHP_INT_MAX,"outstart=2000"]); //13106 outputs (tx_id=101172926)


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
