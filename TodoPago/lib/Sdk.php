<?php 
namespace TodoPago;

define('TODOPAGO_VERSION','1.1.1');
define('TODOPAGO_ENDPOINT_TEST','https://developers.todopago.com.ar/');
define('TODOPAGO_ENDPOINT_PROD','https://apis.todopago.com.ar/');
define('TODOPAGO_ENDPOINT_TENATN', 't/1.1/');
define('TODOPAGO_ENDPOINT_SOAP_APPEND', 'services/');

define('TODOPAGO_WSDL_AUTHORIZE',dirname(__FILE__).'/Authorize.wsdl');

class Sdk
{
	private $host = NULL;
	private $port = NULL;
	private $user = NULL;
	private $pass = NULL;
	private $connection_timeout = NULL;
	private $local_cert = NULL;
	private $end_point = NULL;
	
	public function __construct($header_http_array, $mode = "test"){
		$this->wsdl = array("Authorize" => TODOPAGO_WSDL_AUTHORIZE);
		
		if($mode == "test") {
			$this->end_point = TODOPAGO_ENDPOINT_TEST;
		} elseif ($mode == "prod") {
			$this->end_point = TODOPAGO_ENDPOINT_PROD;	
		}
		
		$this->header_http = $this->getHeaderHttp($header_http_array);
	
	}

	private function getHeaderHttp($header_http_array){
		$header = "";
		foreach($header_http_array as $key=>$value){
			$header .= "$key : $value\r\n";
		}
		
		return $header;
	}
	/*
	* configuraciones
	/

	/**
	* Setea parametros en caso de utilizar proxy
	* ejemplo:
	* $todopago->setProxyParameters('199.0.1.33', '80', 'usuario','contrasenya');
	*/
	public function setProxyParameters($host = null, $port = null, $user = null, $pass = null){
		$this->host = $host;
		$this->port = $port;
		$this->user = $user;
		$this->pass = $pass;
	}
	
	/**
	* Setea time out (deaulft=NULL)
	* ejemplo:
	* $todopago->setConnectionTimeout(1000);
	*/
	public function setConnectionTimeout($connection_timeout){
		$this->connection_timeout = $connection_timeout;
	}
	
	/**
	* Setea ruta del certificado .pem (deaulft=NULL)
	* ejemplo:
	* $todopago->setLocalCert('c:/miscertificados/decidir.pem');
	*/	
	public function setLocalCert($local_cert){
		$this->local_cert= file_get_contents($local_cert);
	}
	

	/*
	* GET_PAYMENT_VALUES
	*/

	public function sendAuthorizeRequest($options_comercio, $options_operacion){
		// parseo de los valores enviados por el e-commerce/custompage
		$authorizeRequest = $this->parseToAuthorizeRequest($options_comercio, $options_operacion);
		
		$authorizeRequestResponse = $this->getAuthorizeRequestResponse($authorizeRequest);

		//devuelve el formato de array el resultado de de la operación SendAuthorizeRequest
		$authorizeRequestResponseValues = $this->parseAuthorizeRequestResponseToArray($authorizeRequestResponse);

		return $authorizeRequestResponseValues;
	}

	private function parseToAuthorizeRequest($options_comercio, $options_operacion){
		$authorizeRequest = (object)$options_comercio;
		$authorizeRequest->Payload = $this->getPayload($options_operacion);
		return $authorizeRequest;
	}

	private function getClientSoap($typo){
		$local_wsdl = $this->wsdl["$typo"];
		$local_end_point = $this->end_point.TODOPAGO_ENDPOINT_SOAP_APPEND.TODOPAGO_ENDPOINT_TENATN."$typo";
		$context = array('http' =>
			array(
				'header'  => $this->header_http
							
			)
		);
         
		$clientSoap = new \SoapClient($local_wsdl, array(
				
				'stream_context' => stream_context_create($context),
				'local_cert'=>($this->local_cert), 
				'connection_timeout' => $this->connection_timeout,
				'location' => $local_end_point,
				'encoding' => 'UTF-8',
				'proxy_host' => $this->host,
				'proxy_port' => $this->port,
				'proxy_login' => $this->user,
				'proxy_password' => $this->pass
			));

		return $clientSoap;
	}

	private function getAuthorizeRequestResponse($authorizeRequest){
		$clientSoap = $this->getClientSoap('Authorize');

		$authorizeRequestResponse = $clientSoap->SendAuthorizeRequest($authorizeRequest);

		return $authorizeRequestResponse;
	}

	private function parseAuthorizeRequestResponseToArray($authorizeRequestResponse){
		$authorizeRequestResponseOptions = json_decode(json_encode($authorizeRequestResponse), true);

		return $authorizeRequestResponseOptions;
	}
	
	public static function sanitizeValue($string){
            	$string = htmlspecialchars_decode($string);

            	$re = "/\\[(.*?)\\]|<(.*?)\\>/i";
            	$subst = "";
            	$string = preg_replace($re, $subst, $string);

            	$replace = array("!","'","\'","\"","  ","$","\\","\n","\r",'\n','\r','\t',"\t","\n\r",'\n\r','&nbsp;','&ntilde;',".,",",.");
            	$string = str_replace($replace, '', $string);

            	$cods = array('\u00c1','\u00e1','\u00c9','\u00e9','\u00cd','\u00ed','\u00d3','\u00f3','\u00da','\u00fa','\u00dc','\u00fc','\u00d1','\u00f1');
            	$susts = array('Á','á','É','é','Í','í','Ó','ó','Ú','ú','Ü','ü','Ṅ','ñ');
            	$string = str_replace($cods, $susts, $string);		
            
		return $string;
	}
	
	private function getPayload($optionsAuthorize){
		$xmlPayload = "<Request>";
		foreach($optionsAuthorize as $key => $value){
	
			$xmlPayload .= "<" . $key . ">" . self::sanitizeValue($value) . "</" . $key . ">";
		}
		$xmlPayload .= "</Request>";

		//Paso a UTF-8.
		if(function_exists("mb_convert_encoding")) return mb_convert_encoding($xmlPayload, "UTF-8", "auto");    
        else return utf8_encode($xmlPayload);
	}

	/*
	* QUERY_PAYMENT
	*/

	public function getAuthorizeAnswer($optionsAnswer){
		$authorizeAnswer = $this->parseToAuthorizeAnswer($optionsAnswer);

		$authorizeAnswerResponse = $this->getAuthorizeAnswerResponse($authorizeAnswer);

		$authorizeAnswerResponseValues = $this->parseAuthorizeAnswerResponseToArray($authorizeAnswerResponse);

		return $authorizeAnswerResponseValues;
	}

	private function parseToAuthorizeAnswer($optionsAnswer){
		
		$obj_options_answer = (object) $optionsAnswer;
		
		return $obj_options_answer;
	}

	private function getAuthorizeAnswerResponse($authorizeAnswer){
		$client = $this->getClientSoap('Authorize');
		$authorizeAnswer = $client->GetAuthorizeAnswer($authorizeAnswer);
		return $authorizeAnswer;
	}

	private function parseAuthorizeAnswerResponseToArray($authorizeAnswerResponse){
		$authorizeAnswerResponseOptions = json_decode(json_encode($authorizeAnswerResponse), true);

		return $authorizeAnswerResponseOptions;
	}
	
	//REST
	public function getStatus($arr_datos_status){
		$url = $this->end_point.TODOPAGO_ENDPOINT_TENATN.'api/Operations/GetByOperationId/MERCHANT/'. $arr_datos_status["MERCHANT"] . '/OPERATIONID/'. $arr_datos_status["OPERATIONID"];
		return $this->doRest($url);
	}
	public function getAllPaymentMethods($arr_datos_merchant){
		$url = $this->end_point.TODOPAGO_ENDPOINT_TENATN.'api/PaymentMethods/Get/MERCHANT/'. $arr_datos_merchant["MERCHANT"];
		return $this->doRest($url);
	}
	
	private function doRest($url){
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		curl_close($curl);
		return json_decode(json_encode(simplexml_load_string($result)), true);
	}
}
