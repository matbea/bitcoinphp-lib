<?php

/*
 * This file is part of the BTCBridge package.
 *
 * (c) Matbea <mail@matbea.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BTCBridge\Handler;

/**
 * Returns data to user's btc-requests using Matbea-API
 * @author Matbea <mail@matbea.com>
 */
class BlockCypherHandler extends AbstractHandler {

	protected $token = "dc20a175f3594965a8f4707cdcf58a32";
	protected $browser = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36';
	protected $url = "https://api.blockcypher.com/v1/btc/main/";

    /**
     * @param Boolean $ordered Whether the order of processor will saved or not
     *
	 * @throws InvalidArgumentException If passed url is not a valid url
     */
    public function __construct($ordered = false) {
        parent::__construct($ordered);
		//if ( filter_var($url, FILTER_VALIDATE_URL) === FALSE ) {
			//throw new \InvalidArgumentException('An url must be valid.');
		//}
		//$this->url = $url;
    }

	/**
	 * Prepare curl descriptor for querying
	 *
	 * @param string  $url An url address for connecting
	 *
	 * @return void
	 */
	private function prepare_curl(&$curl,$url) {
		if ( !curl_setopt($curl, CURLOPT_URL,            $url            ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_USERAGENT,      $this->browser  ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1               ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_HEADER,         0               ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0               ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0               ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
	}

    /**
     * {@inheritdoc}
     */
	public function listtransactions($address,array $options=array()) {
		//$unspentOnly=false,$includeScript=false,$includeConfidence=false,$before=NULL,$after=NULL,$limit=200,$confirmations=NULL,$confidence=NULL,$omitWalletAddresses=false
		if ( "string" != gettype($address) || ( "" == $address ) ) {
			throw new \InvalidArgumentException("address variable must be non empty string.");
		}
		$url  = $this->url . "addrs/" . $address;
		$sep = "?";
		if ( array_key_exists('unspentOnly',$options)         && ( TRUE === $options['unspentOnly'] ) ) { $url .= $sep . "unspentOnly=true"; $sep = "&"; }
		if ( array_key_exists('includeScript',$options)       && ( TRUE === $options['includeScript'] ) ) { $url .= $sep . "includeScript=true"; $sep = "&"; }
		if ( array_key_exists('includeConfidence',$options)   && ( TRUE === $options['includeConfidence'] )  ) { $url .= $sep . "includeConfidence=true"; $sep = "&"; }
		if ( array_key_exists('before',$options)              && ( NULL !== $options['before'] ) ) { $url .= $sep . "before=" . $options['before']; $sep = "&"; }
		if ( array_key_exists('after',$options)               && ( NULL !== $options['after'] ) ) { $url .= $sep . "after=" . $options['after']; $sep = "&"; }
		if ( array_key_exists('limit',$options)               && ( 200 !== $options['limit'] ) ) { $url .= $sep . "limit=" . $options['limit']; $sep = "&"; }
		if ( array_key_exists('confirmations',$options)       && ( NULL !== $options['confirmations'] ) ) { $url .= $sep . "confirmations=" . $options['confirmations']; $sep = "&"; }
		if ( array_key_exists('confidence',$options)          && ( NULL !== $options['confidence'] ) ) { $url .= $sep . "confidence=" . $options['$confidence']; $sep = "&"; }
		if ( array_key_exists('omitWalletAddresses',$options) && ( TRUE !== $options['omitWalletAddresses'] ) ) { $url .= $sep . "omitWalletAddresses=true"; $sep = "&"; }

		$awaiting_params = ['unspentOnly','includeScript','includeConfidence','before','after','limit','confirmations','confidence','omitWalletAddresses'];

		foreach ( $options as $opt_name => $opt_val ) {
			if ( !in_array($opt_name,$awaiting_params) ) {
				$this->logger->warning("Method \"" . __METHOD__ . "\" does not accept option \"" . $opt_name . "\".");
			}
		}

		$ch = curl_init();
		$this->prepare_curl($ch,$url);
		$content = curl_exec($ch);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl error occured (url:\"" . $url . "\")");
		}
		$content = json_decode($content,true);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
		}
		return $content;
	}

    /**
     * {@inheritdoc}
     */
    public function gettransaction($TXHASH,array $options = array()) {
		if ( "string" != gettype($TXHASH) || ( "" == $TXHASH ) ) {
			throw new \InvalidArgumentException("TXHASH variable must be non empty string.");
		}

		$url  = $this->url . "txs/" . $TXHASH;

		$sep = "?";
		if ( array_key_exists('limit',$options)               && ( 20 !== $options['limit'] ) ) { $url .= $sep . "limit=" . $options['limit']; $sep = "&"; }
		if ( array_key_exists('instart',$options)             && ( NULL !== $options['instart'] ) ) { $url .= $sep . "instart=" . $options['instart']; $sep = "&"; }
		if ( array_key_exists('outstart',$options)            && ( NULL !== $options['outstart'] ) ) { $url .= $sep . "outstart=" . $options['outstart']; $sep = "&"; }
		if ( array_key_exists('includeHex',$options)          && ( TRUE === $options['includeHex'] ) ) { $url .= $sep . "includeHex=true"; $sep = "&"; }
		if ( array_key_exists('includeConfidence',$options)   && ( TRUE === $options['includeConfidence'] )  ) { $url .= $sep . "includeConfidence=true"; $sep = "&"; }

		$awaiting_params = ['limit','instart','outstart','includeHex','includeConfidence'];

		foreach ( $options as $opt_name => $opt_val ) {
			if ( !in_array($opt_name,$awaiting_params) ) {
				$this->logger->warning("Method \"" . __METHOD__ . "\" does not accept option \"" . $opt_name . "\".");
			}
		}

		$ch = curl_init();
		$this->prepare_curl($ch,$url);
		$content = curl_exec($ch);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl error occured (url:\"" . $url . "\")");
		}
		$content = json_decode($content,true);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
		}
		return $content;
	}

    /**
     * {@inheritdoc}
     */
    public function getbalance($Account,$Confirmations=1,$IncludeWatchOnly=false) {
		if ( "string" != gettype($Account) || ( "" == $Account ) ) {
			throw new \InvalidArgumentException("Account variable must be non empty string.");
		}
		$url  = $this->url . "addrs/" . $Account . "?token=" . $this->token . "&confirmations=" . $Confirmations;
		$ch = curl_init();
		$this->prepare_curl($ch,$url);
		$content = curl_exec($ch);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl error occured (url:\"" . $url . "\")");
		}
		$content = json_decode($content,true);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
		}
		if ( !isset($content["balance"]) ) {
			$this->logger->error("Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.",["data"=>$content]);
			throw new \RuntimeException("Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.");
		}
		return floatval($content["balance"]) / ( 100 * 1000 * 1000 ); //HUERAGA - дробные числа, все дела... с epsilon сравнивать. может лучше в сашитос возвращать?
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($Account) {
		if ( "string" != gettype($Account) || ( "" == $Account ) ) {
			throw new \InvalidArgumentException("Account variable must be non empty string.");
		}
		$url  = $this->url . "addrs/" . $Account . "?token=" . $this->token;
		$ch = curl_init();
		$this->prepare_curl($ch,$url);
		$content = curl_exec($ch);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl error occured (url:\"" . $url . "\")");
		}
		$content = json_decode($content,true);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
		}
		if ( !isset($content["unconfirmed_balance"]) ) {
			$this->logger->error("Answer of url: \"" . $url . "\")  does not contain a \"unconfirmed_balance\" field.",["data"=>$content]);
			throw new \RuntimeException("Answer of url: \"" . $url . "\")  does not contain a \"unconfirmed_balance\" field.");
		}
		return floatval($content["unconfirmed_balance"]) / ( 100 * 1000 * 1000 ); //HUERAGA - дробные числа, все дела... с epsilon сравнивать. может лучше в сашитос возвращать?
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($Account,$MinimumConfirmations=1) {
		if ( "string" != gettype($Account) || ( "" == $Account ) ) {
			throw new \InvalidArgumentException("Account variable must be non empty string.");
		}
		$url  = $this->url . "addrs/" . $Account . "?token=" . $this->token . "&unspentOnly=true&confirmations=" . $MinimumConfirmations;
		$ch = curl_init();
		$this->prepare_curl($ch,$url);
		$content = curl_exec($ch);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl error occured (url:\"" . $url . "\")");
		}
		$content = json_decode($content,true);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
		}
		if ( ( !isset($content["txrefs"]) ) && ( !isset($content["unconfirmed_txrefs"]) ) ) {
			return [];
		}

		$necessary_fields = ['tx_output_n','tx_hash','value','confirmations'];

		$result = [];


		if ( ( 0 == $MinimumConfirmations ) && isset($content["unconfirmed_txrefs"]) ) {
			foreach ( $content["unconfirmed_txrefs"] as $rec ) {
				foreach ( $necessary_fields as $f ) {
					if ( !isset($rec[$f]) ) {
						throw new \RuntimeException("Item of unconfirmed_txrefs array does not contain \"" . $f . "\" field (url:\"" . $url . "\").");
					}
				}
				if ( intval($rec['tx_output_n']) < 0 ) {
					continue; //according to https://www.blockcypher.com/dev/bitcoin/?shell#txref if tx_output_n is negative then this is input, we look for outputs only
				}
				$item = [];
				$item["txid"] = $rec['tx_hash'];
				$item["vout"] = $rec['tx_output_n'];
				$item["address"] = $Account;
				$item["scriptPubey"] = "demo"; //HUERAGA
				$item["amount"] = floatval($rec['value'])/100000000;
				$item["confirmations"] = $rec['confirmations'];
				$item["spendable"] = false;
				$result [] = $item;
			}
		}

		if ( isset($content["txrefs"]) ) {
			foreach ( $content["txrefs"] as $rec ) {
				foreach ( $necessary_fields as $f ) {
					if ( !isset($rec[$f]) ) {
						throw new \RuntimeException("Item of txrefs array does not contain \"" . $f . "\" field (url:\"" . $url . "\").");
					}
				}
				if ( intval($rec['tx_output_n']) < 0 ) {
					continue; //according to https://www.blockcypher.com/dev/bitcoin/?shell#txref if tx_output_n is negative then this is input, we look for outputs only
				}
				$item = [];
				$item["txid"] = $rec['tx_hash'];
				$item["vout"] = $rec['tx_output_n'];
				$item["address"] = $Account;
				$item["scriptPubkey"] = "demo"; //HUERAGA
				$item["amount"] = floatval($rec['value'])/100000000;
				$item["confirmations"] = intval($rec['confirmations']);
				$item["spendable"] = false;
				$result [] = $item;
			}
		}
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function sendrawtransaction($Transaction) {
		$url = $this->url . "txs/push?token=" . $this->token;
		$post_data = '{"tx":"'.$Transaction.'"}';
		$curl = curl_init();
		if ( !curl_setopt($curl, CURLOPT_URL,            $url            ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_USERAGENT,      $this->browser  ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1               ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0               ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0               ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_POST, 1                         ) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']) ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		if ( !curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data )        ) { throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\")."); }
		$content = curl_exec($curl);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl error occured (url:\"" . $url . "\", post: \"" . $post_data . "\").");
		}
		$content = json_decode($content,true);
		if ( FALSE === $content ) {
			throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
		}
		if ( isset($content['error']) ) {
			throw new \RuntimeException("Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\", post: \"" . $post_data . "\").");
		}
		if ( !isset($content['tx']) ) {
			throw new \RuntimeException("Answer does not contain \"tx\" field (url:\"" . $url . "\", post: \"" . $post_data . "\").");
		}
		if ( !isset($content['tx']['hash']) ) {
			throw new \RuntimeException("Answer does not contain \"hash\" field in \"tx\" array (url:\"" . $url . "\", post: \"" . $post_data . "\").");
		}
		return $content['tx']['hash'];

    }

}
