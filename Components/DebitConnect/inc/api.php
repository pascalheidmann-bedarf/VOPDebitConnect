<?php
class API_VOP{

	var $APIURLBoni = "https://api.eaponline.de/bonigateway.php?wsdl";
	var $APIURLTest = "https://api.eaponline.de/bonigateway.php?wsdl";
	var $APIURLMahnwesen = "https://api.eaponline.de/debitconnect.php?wsdl";
	var $shopware;
	function boni()
	{
		return new SoapClient($this->APIURLBoni,array(  'encoding' => 'UTF-8', 'cache_wsdl' => WSDL_CACHE_NONE,'trace' => 1));
	}
	function test()
	{
		return new SoapClient($this->APIURLTest,array(  'encoding' => 'UTF-8', 'cache_wsdl' => WSDL_CACHE_NONE,'trace' => 1));
	}
	function mahnwesen()
	{
		return new SoapClient($this->APIURLMahnwesen,array(  'encoding' => 'UTF-8', 'cache_wsdl' => WSDL_CACHE_NONE,'trace' => 1));
	}
	
	function __construct()
	{
		if(DC()->settings->currentSetting->shopwareapiuser && DC()->settings->currentSetting->shopwareapikey)
		{
			$this->shopware = new shopwareApi(DC()->settings->currentSetting->shopwareapiurl,DC()->settings->currentSetting->shopwareapiuser,DC()->settings->currentSetting->shopwareapikey);
		}
	}

	function sendVersionInformation(){
	    $api = $this->mahnwesen();
        $handle = md5("D3B!7C0NN3CT_".date("Ymd")."AUTH_TOKEN");
	    $api->version_statistic($handle,DC()->getDBVersion(),Shopware::VERSION,$_SERVER['HTTP_HOST'],'Shopware');
    }
}

class shopwareApi
{
    const METHOD_GET = 'GET';
    const METHOD_PUT = 'PUT';
    const METHOD_POST = 'POST';
    const METHOD_DELETE = 'DELETE';
    protected $validMethods = array(
        self::METHOD_GET,
        self::METHOD_PUT,
        self::METHOD_POST,
        self::METHOD_DELETE,
    );
    protected $apiUrl;
    protected $cURL;

    public function __construct($apiUrl, $username, $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        //Initializes the cURL instance
        $this->cURL = curl_init();
        curl_setopt($this->cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->cURL, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->cURL, CURLOPT_USERAGENT, 'Shopware ApiClient');
        curl_setopt($this->cURL, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($this->cURL, CURLOPT_USERPWD, $username . ':' . $apiKey);
        curl_setopt(
            $this->cURL,
            CURLOPT_HTTPHEADER,
            array('Content-Type: application/json; charset=utf-8')
        );
    }

    public function call($url, $method = self::METHOD_GET, $data = array(), $params = array())
    {
        if (!in_array($method, $this->validMethods)) {
            throw new Exception('Invalid HTTP-Methode: ' . $method);
        }
        $queryString = '';
        if (!empty($params)) {
            $queryString = http_build_query($params);
        }
        $url = rtrim($url, '?') . '?';
        $url = $this->apiUrl . $url . $queryString;
		//DC()->LOG("API",$url." ".$queryString,0);
        $dataString = json_encode($data);
        curl_setopt($this->cURL, CURLOPT_URL, $url);
        curl_setopt($this->cURL, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->cURL, CURLOPT_POSTFIELDS, $dataString);
        $result = curl_exec($this->cURL);
        $httpCode = curl_getinfo($this->cURL, CURLINFO_HTTP_CODE);

        return $this->prepareResponse($result, $httpCode);
    }

    public function get($url, $params = array())
    {
        return $this->call($url, self::METHOD_GET, array(), $params);
    }

    public function post($url, $data = array(), $params = array())
    {
        return $this->call($url, self::METHOD_POST, $data, $params);
    }

    public function put($url, $data = array(), $params = array())
    {
        return $this->call($url, self::METHOD_PUT, $data, $params);
    }

    public function delete($url, $params = array())
    {
        return $this->call($url, self::METHOD_DELETE, array(), $params);
    }



    protected function prepareResponse($result, $httpCode)
    {
      
        if (null === $decodedResult = json_decode($result, true)) {
            $jsonErrors = array(
                JSON_ERROR_NONE => 'No error occurred',
                JSON_ERROR_DEPTH => 'The maximum stack depth has been reached',
                JSON_ERROR_CTRL_CHAR => 'Control character issue, maybe wrong encoded',
                JSON_ERROR_SYNTAX => 'Syntaxerror',
            );
			throw new Exception($jsonErrors[json_last_error()]);

            return;
        }
        if (!isset($decodedResult['success'])) {
           throw new Exception("Invalid Response");

            return;
        }
        if (!$decodedResult['success']) {
            if (array_key_exists('errors', $decodedResult) && is_array($decodedResult['errors'])) {
				   throw new Exception($decodedResult['message']." ".$decodedResult['errors']);
            }

            return;
        }
     
        if (isset($decodedResult['data'])) {
            DC()->View("API_DEBUG",$decodedResult['data']);;
        }

        return $decodedResult;
    }
}

 class templates{

public static function vopTPL()
{
	$tpl = "PCFET0NUWVBFIGh0bWwgUFVCTElDICItLy9XM0MvL0RURCBYSFRNTCAxLjAgVHJhbnNpdGlvbmFsLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL1RSL3hodG1sMS9EVEQveGh0bWwxLXRyYW5zaXRpb25hbC5kdGQiPjxodG1sIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hodG1sIj48aGVhZD4gICAgICAgIDwhLS0gTGl6ZW56IGbDvHIgZGllc2VzIE1haWx0ZW1wbGF0ZTogQ0MgQlktU0EgMy4wIC0tPiAgICAgICAgPCEtLSBRdWVsbGU6IGh0dHBzOi8vZ2l0aHViLmNvbS9tYWlsY2hpbXAvRW1haWwtQmx1ZXByaW50cywgYW5nZXBhc3N0IGbDvHIgSlRMLVdhd2kgLS0+ICAgICAgICA8bWV0YSBodHRwLWVxdWl2PSJDb250ZW50LVR5cGUiIGNvbnRlbnQ9InRleHQvaHRtbDsgY2hhcnNldD1VVEYtOCIgLz4gICAgPHRpdGxlPkRlYml0Q29ubmVjdCBaYWhsdW5nc2VycmluZXJ1bmc8L3RpdGxlPjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+ICAgICAgICAgICAgICAgICAgLyogL1wvXC9cL1wvXC9cL1wvXC8gQ0xJRU5ULVNQRUNJRklDIFNUWUxFUyAvXC9cL1wvXC9cL1wvXC9cLyAqLyAgICAgICAgICAgICNvdXRsb29rIGF7cGFkZGluZzowO30gLyogRm9yY2UgT3V0bG9vayB0byBwcm92aWRlIGEgInZpZXcgaW4gYnJvd3NlciIgbWVzc2FnZSAqLyAgICAgICAgICAgIC5SZWFkTXNnQm9keXt3aWR0aDoxMDAlO30gLkV4dGVybmFsQ2xhc3N7d2lkdGg6MTAwJTt9IC8qIEZvcmNlIEhvdG1haWwgdG8gZGlzcGxheSBlbWFpbHMgYXQgZnVsb";$tpl.="CB3aWR0aCAqLyAgICAgICAgICAgIC5FeHRlcm5hbENsYXNzLCAuRXh0ZXJuYWxDbGFzcyBwLCAuRXh0ZXJuYWxDbGFzcyBzcGFuLCAuRXh0ZXJuYWxDbGFzcyBmb250LCAuRXh0ZXJuYWxDbGFzcyB0ZCwgLkV4dGVybmFsQ2xhc3MgZGl2IHtsaW5lLWhlaWdodDogMTAwJTt9IC8qIEZvcmNlIEhvdG1haWwgdG8gZGlzcGxheSBub3JtYWwgbGluZSBzcGFjaW5nICovICAgICAgICAgICAgYm9keSwgdGFibGUsIHRkLCBwLCBhLCBsaSwgYmxvY2txdW90ZXstd2Via2l0LXRleHQtc2l6ZS1hZGp1c3Q6MTAwJTsgLW1zLXRleHQtc2l6ZS1hZGp1c3Q6MTAwJTt9IC8qIFByZXZlbnQgV2ViS2l0IGFuZCBXaW5kb3dzIG1vYmlsZSBjaGFuZ2luZyBkZWZhdWx0IHRleHQgc2l6ZXMgKi8gICAgICAgICAgICB0YWJsZSwgdGR7bXNvLXRhYmxlLWxzcGFjZTowcHQ7IG1zby10YWJsZS1yc3BhY2U6MHB0O30gLyogUmVtb3ZlIHNwYWNpbmcgYmV0d2VlbiB0YWJsZXMgaW4gT3V0bG9vayAyMDA3IGFuZCB1cCAqLyAgICAgICAgICAgIGltZ3stbXMtaW50ZXJwb2xhdGlvbi1tb2RlOmJpY3ViaWM7fSAvKiBBbGxvdyBzbW9vdGhlciByZW5kZXJpbmcgb2YgcmVzaXplZCBpbWFnZSBpbiBJbnRlcm5ldCBFeHBsb3JlciAqLyAgICAgICAgICAgIC8qIC9cL1wvXC9cL1wvXC9cL1wvIFJFU0VUIFNUWUxFUyAvXC9cL1wvXC9cL1wvXC9cLyAqLyAgICAgICAgICAgIGJvZHl7bWFyZ2luOjA7IHBhZGRpbmc6MDt9ICAgICAgICAgICAgaW1ne2JvcmRlcjowOyBoZWlnaHQ";$tpl.="6YXV0bzsgbGluZS1oZWlnaHQ6MTAwJTsgb3V0bGluZTpub25lOyB0ZXh0LWRlY29yYXRpb246bm9uZTt9ICAgICAgICAgICAgdGFibGV7Ym9yZGVyLWNvbGxhcHNlOmNvbGxhcHNlICFpbXBvcnRhbnQ7fSAgICAgICAgICAgIGJvZHksICNib2R5VGFibGUsICNib2R5Q2VsbHtoZWlnaHQ6MTAwJSAhaW1wb3J0YW50OyBtYXJnaW46MDsgcGFkZGluZzowOyB3aWR0aDoxMDAlICFpbXBvcnRhbnQ7fSAgICAgICAgICAgIC8qIC9cL1wvXC9cL1wvXC9cL1wvIFRFTVBMQVRFIFNUWUxFUyAvXC9cL1wvXC9cL1wvXC9cLyAqLyAgICAgICAgICAgIC8qID09PT09PT09PT0gUGFnZSBTdHlsZXMgPT09PT09PT09PSAqLyAgICAgICAgICAgICNib2R5Q2VsbHtwYWRkaW5nOjIwcHg7fSAgICAgICAgICAgICN0ZW1wbGF0ZUNvbnRhaW5lcnt3aWR0aDo4MDBweDt9ICAgICAgICAgICAgLyoqICAgICAgICAgICAgKiBAdGFiIFBhZ2UgICAgICAgICAgICAqIEBzZWN0aW9uIGJhY2tncm91bmQgc3R5bGUgICAgICAgICAgICAqIEB0aXAgU2V0IHRoZSBiYWNrZ3JvdW5kIGNvbG9yIGFuZCB0b3AgYm9yZGVyIGZvciB5b3VyIGVtYWlsLiBZb3UgbWF5IHdhbnQgdG8gY2hvb3NlIGNvbG9ycyB0aGF0IG1hdGNoIHlvdXIgY29tcGFueSdzIGJyYW5kaW5nLiAgICAgICAgICAgICogQHRoZW1lIHBhZ2UgICAgICAgICAgICAqLyAgICAgICAgICAgIGJvZHksICNib2R5VGFibGV7CS8qQGVkaXRhYmxlKi8gYmFja2dyb3VuZC1jb2xvcjojZmZmOyAgICAgICAgICAgIH0g";
$tpl.="ICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgUGFnZSAgICAgICAgICAgICogQHNlY3Rpb24gYmFja2dyb3VuZCBzdHlsZSAgICAgICAgICAgICogQHRpcCBTZXQgdGhlIGJhY2tncm91bmQgY29sb3IgYW5kIHRvcCBib3JkZXIgZm9yIHlvdXIgZW1haWwuIFlvdSBtYXkgd2FudCB0byBjaG9vc2UgY29sb3JzIHRoYXQgbWF0Y2ggeW91ciBjb21wYW55J3MgYnJhbmRpbmcuICAgICAgICAgICAgKiBAdGhlbWUgcGFnZSAgICAgICAgICAgICovICAgICAgICAgICAgI2JvZHlDZWxseyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovOyAgICAgICAgICAgIH0gICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgUGFnZSAgICAgICAgICAgICogQHNlY3Rpb24gZW1haWwgYm9yZGVyICAgICAgICAgICAgKiBAdGlwIFNldCB0aGUgYm9yZGVyIGZvciB5b3VyIGVtYWlsLiAgICAgICAgICAgICovICAgICAgICAgICAgI3RlbXBsYXRlQ29udGFpbmVyeyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGJvcmRlcjoxcHggc29saWQgI0JCQkJCQjsgICAgICAgICAgICB9ICAgICAgICAgICAgLyoqICAgICAgICAgICAgKiBAdGFiIFBhZ2UgICAgICAgICAgICAqIEBzZWN0aW9uIGhlYWRpbmcgMSAgICAgICAgICAgICogQHRpcCBTZXQgdGhlIHN0eWxpbmcgZm9yIGFsbCBmaXJzdC1sZXZlbCBoZWFkaW5ncyBpbiB5b3VyIGVtYWlscy4gVGhlc2Ugc2hvdWxkIGJlIHRoZSBsYXJnZXN0IG9mIHlvdXIgaGVhZGluZ3MuICAgICAgICAgICA";$tpl.="gKiBAc3R5bGUgaGVhZGluZyAxICAgICAgICAgICAgKi8gICAgICAgICAgICBoMXsJLypAZWRpdGFibGUqLyBjb2xvcjojZmZmICFpbXBvcnRhbnQ7CWRpc3BsYXk6YmxvY2s7CS8qQGVkaXRhYmxlKi8gZm9udC1mYW1pbHk6SGVsdmV0aWNhOwkvKkBlZGl0YWJsZSovIGZvbnQtc2l6ZToyNnB4OwkvKkBlZGl0YWJsZSovIGZvbnQtc3R5bGU6bm9ybWFsOwkvKkBlZGl0YWJsZSovIGZvbnQtd2VpZ2h0OmJvbGQ7CS8qQGVkaXRhYmxlKi8gbGluZS1oZWlnaHQ6MTAwJTsJLypAZWRpdGFibGUqLyBsZXR0ZXItc3BhY2luZzpub3JtYWw7CW1hcmdpbi10b3A6MDsJbWFyZ2luLXJpZ2h0OjA7CW1hcmdpbi1ib3R0b206MTBweDsJbWFyZ2luLWxlZnQ6MDsJLypAZWRpdGFibGUqLyB0ZXh0LWFsaWduOmxlZnQ7ICAgICAgICAgICAgfSAgICAgICAgICAgIC8qKiAgICAgICAgICAgICogQHRhYiBQYWdlICAgICAgICAgICAgKiBAc2VjdGlvbiBoZWFkaW5nIDIgICAgICAgICAgICAqIEB0aXAgU2V0IHRoZSBzdHlsaW5nIGZvciBhbGwgc2Vjb25kLWxldmVsIGhlYWRpbmdzIGluIHlvdXIgZW1haWxzLiAgICAgICAgICAgICogQHN0eWxlIGhlYWRpbmcgMiAgICAgICAgICAgICovICAgICAgICAgICAgaDJ7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gY29sb3I6IzQwNDA0MCAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICBkaXNwbGF5OmJsb2NrOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtZmFtaWx5OkhlbHZldGljYTsgICAg";$tpl.="ICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXNpemU6MjBweDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXN0eWxlOm5vcm1hbDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXdlaWdodDpib2xkOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGxpbmUtaGVpZ2h0OjEwMCU7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gbGV0dGVyLXNwYWNpbmc6bm9ybWFsOyAgICAgICAgICAgICAgICBtYXJnaW4tdG9wOjA7ICAgICAgICAgICAgICAgIG1hcmdpbi1yaWdodDowOyAgICAgICAgICAgICAgICBtYXJnaW4tYm90dG9tOjEwcHg7ICAgICAgICAgICAgICAgIG1hcmdpbi1sZWZ0OjA7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gdGV4dC1hbGlnbjpsZWZ0OyAgICAgICAgICAgIH0gICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgUGFnZSAgICAgICAgICAgICogQHNlY3Rpb24gaGVhZGluZyAzICAgICAgICAgICAgKiBAdGlwIFNldCB0aGUgc3R5bGluZyBmb3IgYWxsIHRoaXJkLWxldmVsIGhlYWRpbmdzIGluIHlvdXIgZW1haWxzLiAgICAgICAgICAgICogQHN0eWxlIGhlYWRpbmcgMyAgICAgICAgICAgICovICAgICAgICAgICAgaDN7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gY29sb3I6IzYwNjA2MCAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICBkaXNwbGF5OmJsb2NrOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtZmFtaWx5OkhlbHZld";$tpl.="GljYTsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXNpemU6MTZweDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXN0eWxlOml0YWxpYzsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXdlaWdodDpub3JtYWw7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gbGluZS1oZWlnaHQ6MTAwJTsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBsZXR0ZXItc3BhY2luZzpub3JtYWw7ICAgICAgICAgICAgICAgIG1hcmdpbi10b3A6MDsgICAgICAgICAgICAgICAgbWFyZ2luLXJpZ2h0OjA7ICAgICAgICAgICAgICAgIG1hcmdpbi1ib3R0b206MTBweDsgICAgICAgICAgICAgICAgbWFyZ2luLWxlZnQ6MDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyB0ZXh0LWFsaWduOmxlZnQ7ICAgICAgICAgICAgfSAgICAgICAgICAgIC8qKiAgICAgICAgICAgICogQHRhYiBQYWdlICAgICAgICAgICAgKiBAc2VjdGlvbiBoZWFkaW5nIDQgICAgICAgICAgICAqIEB0aXAgU2V0IHRoZSBzdHlsaW5nIGZvciBhbGwgZm91cnRoLWxldmVsIGhlYWRpbmdzIGluIHlvdXIgZW1haWxzLiBUaGVzZSBzaG91bGQgYmUgdGhlIHNtYWxsZXN0IG9mIHlvdXIgaGVhZGluZ3MuICAgICAgICAgICAgKiBAc3R5bGUgaGVhZGluZyA0ICAgICAgICAgICAgKi8gICAgICAgICAgICBoNHsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBjb2xvcjojODA4MDgwICFpbXBvcnRhbnQ7ICAgICAgICAgICAgICAgIGRpc3";$tpl.="BsYXk6YmxvY2s7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gZm9udC1mYW1pbHk6SGVsdmV0aWNhOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtc2l6ZToxNHB4OyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtc3R5bGU6aXRhbGljOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtd2VpZ2h0Om5vcm1hbDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBsaW5lLWhlaWdodDoxMDAlOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGxldHRlci1zcGFjaW5nOm5vcm1hbDsgICAgICAgICAgICAgICAgbWFyZ2luLXRvcDowOyAgICAgICAgICAgICAgICBtYXJnaW4tcmlnaHQ6MDsgICAgICAgICAgICAgICAgbWFyZ2luLWJvdHRvbToxMHB4OyAgICAgICAgICAgICAgICBtYXJnaW4tbGVmdDowOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIHRleHQtYWxpZ246bGVmdDsgICAgICAgICAgICB9ICAgICAgICAgICAgLyogPT09PT09PT09PSBIZWFkZXIgU3R5bGVzID09PT09PT09PT0gKi8gICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgSGVhZGVyICAgICAgICAgICAgKiBAc2VjdGlvbiBwcmVoZWFkZXIgc3R5bGUgICAgICAgICAgICAqIEB0aXAgU2V0IHRoZSBiYWNrZ3JvdW5kIGNvbG9yIGFuZCBib3R0b20gYm9yZGVyIGZvciB5b3VyIGVtYWlsJ3MgcHJlaGVhZGVyIGFyZWEuICAgICAgICAgICAgKiBAdGhlbWUgaGVhZGVyICAgICAgICAgICAgKi8gICAgICA";$tpl.="gICAgICAjdGVtcGxhdGVQcmVoZWFkZXJ7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gYmFja2dyb3VuZC1jb2xvcjojRjRGNEY0OyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGJvcmRlci1ib3R0b206MXB4IHNvbGlkICNDQ0NDQ0M7ICAgICAgICAgICAgfSAgICAgICAgICAgIC8qKiAgICAgICAgICAgICogQHRhYiBIZWFkZXIgICAgICAgICAgICAqIEBzZWN0aW9uIHByZWhlYWRlciB0ZXh0ICAgICAgICAgICAgKiBAdGlwIFNldCB0aGUgc3R5bGluZyBmb3IgeW91ciBlbWFpbCdzIHByZWhlYWRlciB0ZXh0LiBDaG9vc2UgYSBzaXplIGFuZCBjb2xvciB0aGF0IGlzIGVhc3kgdG8gcmVhZC4gICAgICAgICAgICAqLyAgICAgICAgICAgIC5wcmVoZWFkZXJDb250ZW50ewkvKkBlZGl0YWJsZSovIGNvbG9yOiNmZmY7CS8qQGVkaXRhYmxlKi8gZm9udC1mYW1pbHk6SGVsdmV0aWNhOwkvKkBlZGl0YWJsZSovIGZvbnQtc2l6ZToxMHB4OwkvKkBlZGl0YWJsZSovIGxpbmUtaGVpZ2h0OjEyNSU7CS8qQGVkaXRhYmxlKi8gdGV4dC1hbGlnbjpsZWZ0OyAgICAgICAgICAgIH0gICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgSGVhZGVyICAgICAgICAgICAgKiBAc2VjdGlvbiBwcmVoZWFkZXIgbGluayAgICAgICAgICAgICogQHRpcCBTZXQgdGhlIHN0eWxpbmcgZm9yIHlvdXIgZW1haWwncyBwcmVoZWFkZXIgbGlua3MuIENob29zZSBhIGNvbG9yIHRoYXQgaGVscHMgdGhlbSBzdGFuZCBvdXQgZnJvbSB5b3VyIHRleHQu";$tpl.="ICAgICAgICAgICAgKi8gICAgICAgICAgICAucHJlaGVhZGVyQ29udGVudCBhOmxpbmssIC5wcmVoZWFkZXJDb250ZW50IGE6dmlzaXRlZCwgLyogWWFob28hIE1haWwgT3ZlcnJpZGUgKi8gLnByZWhlYWRlckNvbnRlbnQgYSAueXNob3J0Y3V0cyAvKiBZYWhvbyEgTWFpbCBPdmVycmlkZSAqL3sJLypAZWRpdGFibGUqLyBjb2xvcjojZmZmOwkvKkBlZGl0YWJsZSovIGZvbnQtd2VpZ2h0Om5vcm1hbDsJLypAZWRpdGFibGUqLyB0ZXh0LWRlY29yYXRpb246dW5kZXJsaW5lOyAgICAgICAgICAgIH0gICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgSGVhZGVyICAgICAgICAgICAgKiBAc2VjdGlvbiBoZWFkZXIgc3R5bGUgICAgICAgICAgICAqIEB0aXAgU2V0IHRoZSBiYWNrZ3JvdW5kIGNvbG9yIGFuZCBib3JkZXJzIGZvciB5b3VyIGVtYWlsJ3MgaGVhZGVyIGFyZWEuICAgICAgICAgICAgKiBAdGhlbWUgaGVhZGVyICAgICAgICAgICAgKi8gICAgICAgICAgICAjdGVtcGxhdGVIZWFkZXJ7CS8qQGVkaXRhYmxlKi8gYmFja2dyb3VuZC1jb2xvcjojMDAwOwkvKkBlZGl0YWJsZSovIGJvcmRlci10b3A6MXB4IHNvbGlkICNGRkZGRkY7CS8qQGVkaXRhYmxlKi8gYm9yZGVyLWJvdHRvbToxcHggc29saWQgI0NDQ0NDQzsJYmFja2dyb3VuZC1wb3NpdGlvbjogcmlnaHQgYm90dG9tOwliYWNrZ3JvdW5kLXJlcGVhdDogbm8tcmVwZWF0OyAgICAgICAgICAgIH0gICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgSGVhZGVyICAgI";$tpl.="CAgICAgICAgKiBAc2VjdGlvbiBoZWFkZXIgdGV4dCAgICAgICAgICAgICogQHRpcCBTZXQgdGhlIHN0eWxpbmcgZm9yIHlvdXIgZW1haWwncyBoZWFkZXIgdGV4dC4gQ2hvb3NlIGEgc2l6ZSBhbmQgY29sb3IgdGhhdCBpcyBlYXN5IHRvIHJlYWQuICAgICAgICAgICAgKi8gICAgICAgICAgICAuaGVhZGVyQ29udGVudHsJLypAZWRpdGFibGUqLyBjb2xvcjojZmZmOwkvKkBlZGl0YWJsZSovIGZvbnQtZmFtaWx5OkhlbHZldGljYTsJLypAZWRpdGFibGUqLyBmb250LXNpemU6MjBweDsJLypAZWRpdGFibGUqLyBmb250LXdlaWdodDpib2xkOwkvKkBlZGl0YWJsZSovIGxpbmUtaGVpZ2h0OjEwMCU7CS8qQGVkaXRhYmxlKi8gcGFkZGluZy10b3A6MTBweDsJLypAZWRpdGFibGUqLyBwYWRkaW5nLXJpZ2h0OjEwcHg7CS8qQGVkaXRhYmxlKi8gcGFkZGluZy1sZWZ0OjEwcHg7CS8qQGVkaXRhYmxlKi8gdGV4dC1hbGlnbjpsZWZ0OwkvKkBlZGl0YWJsZSovIHZlcnRpY2FsLWFsaWduOm1pZGRsZTsgICAgICAgICAgICB9ICAgICAgICAgICAgLmhlYWRlckxvZ28geyAgICAgICAgICAgICAgICAgICAgICAgIH0gICAgICAgICAgICAgICAgICAgICAgICAuaGVhZGVyU3VtbWFyeSB7CS8qQGVkaXRhYmxlKi8gdGV4dC1hbGlnbjogbGVmdDsgICAgICAgICAgICB9ICAgICAgICAgICAgICAgICAgICAgICAgLyoqICAgICAgICAgICAgKiBAdGFiIEhlYWRlciAgICAgICAgICAgICogQHNlY3Rpb24gaGVhZGVyIGxpbmsgICAgICAgICAgICAqIEB0aXAgU2";$tpl.="V0IHRoZSBzdHlsaW5nIGZvciB5b3VyIGVtYWlsJ3MgaGVhZGVyIGxpbmtzLiBDaG9vc2UgYSBjb2xvciB0aGF0IGhlbHBzIHRoZW0gc3RhbmQgb3V0IGZyb20geW91ciB0ZXh0LiAgICAgICAgICAgICovICAgICAgICAgICAgLmhlYWRlckNvbnRlbnQgYTpsaW5rLCAuaGVhZGVyQ29udGVudCBhOnZpc2l0ZWQsIC8qIFlhaG9vISBNYWlsIE92ZXJyaWRlICovIC5oZWFkZXJDb250ZW50IGEgLnlzaG9ydGN1dHMgLyogWWFob28hIE1haWwgT3ZlcnJpZGUgKi97ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gY29sb3I6I0VCNDEwMjsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXdlaWdodDpub3JtYWw7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gdGV4dC1kZWNvcmF0aW9uOnVuZGVybGluZTsgICAgICAgICAgICB9ICAgICAgICAgICAgICAgICAgICAgICAgLlBvc2l0aW9uSW1hZ2UgeyAgICAgICAgICAgICAgICB3aWR0aDogNTBweDsgICAgICAgICAgICAgICAgaGVpZ2h0OiA1MHB4OyAgICAgICAgICAgIH0gICAgICAgICAgICAgICAgICAgICAgICAuUG9zaXRpb25QcmljZSwgLlN1bVBvc1ByaWNlLCAuUGF5bWVudHNQcmljZSB7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gdmVydGljYWwtYWxpZ246IHRvcDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyB0ZXh0LWFsaWduOiByaWdodDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyB3aWR0aDogMTBlbTsgICAgICAgICAgICB9ICAgICA";$tpl.="gICAgICAgICAgICAgICAgICAgLlN1bVBvc0Rlc2NyaXB0aW9uLCAuUGF5bWVudHNEZXNjcmlwdGlvbiB7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gdGV4dC1hbGlnbjogcmlnaHQ7ICAgICAgICAgICAgfSAgICAgICAgICAgIC5Qb3NpdGlvblByb3BlcnRpZXMgeyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtc2l6ZTogODAlOyAgICAgICAgICAgICAgICBib3JkZXItY29sbGFwc2U6IGNvbGxhcHNlICFpbXBvcnRhbnQ7ICAgICAgICAgICAgICAgIGJvcmRlci1zcGFjaW5nOiA1MHB4IDUwcHggIWltcG9ydGFudDsgICAgICAgICAgICB9ICAgICAgICAgICAgLlBvc2l0aW9uUHJvcGVydGllcyAuc3BhY2VyIGRpdiB7ICAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIHdpZHRoOiAxZW07ICAgICAgICAgICAgfSAgICAgICAgICAgICAgICAgICAgICAgIC5Qb3NpdGlvblByb3BlcnRpZXMgLm5hbWUgeyAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXN0eWxlOiBpdGFsaWM7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gdmVydGljYWwtYWxpZ246IHRvcDsgICAgICAgICAgICAgICAgICAgICAgICAgIH0gICAgICAgICAgICAuUG9zaXRpb25Qcm9wZXJ0aWVzIC5jb250ZW50IHsgICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gd2lkdGg6IDEwMCU7ICAgICAgICAgICAgfSAgICAgICAgICAgIC5Qb3NpdGlvbkNoaWxkcmVuIHsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250";$tpl.="LXNpemU6IDgwJTsgICAgICAgICAgICB9ICAgICAgICAgICAgLlBvc2l0aW9uQ2hpbGRyZW4gLnNwYWNlciBkaXYgeyAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyB3aWR0aDogMWVtOyAgICAgICAgICAgIH0gICAgICAgICAgICAuUG9zaXRpb25DaGlsZHJlbiAuYnVsbGV0IHsgICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gdmVydGljYWwtYWxpZ246IHRvcDsgICAgICAgICAgICB9ICAgICAgICAgICAgLlBvc2l0aW9uQ2hpbGRyZW4gLmNvbnRlbnQgeyAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyB3aWR0aDogMTAwJTsgICAgICAgICAgICAgICAgbWFyZ2luLWxlZnQ6IDFlbTsgICAgICAgICAgICB9ICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5Qb3NpdGlvbkltYWdlIGltZyB7ICAgICAgICAgICAgICAgIHBhZGRpbmc6IDVweDsgICAgICAgICAgICB9ICAgICAgICAgICAgICAgICAgICAgICAgI2hlYWRlckltYWdleyAgICAgICAgICAgICAgICBoZWlnaHQ6YXV0bzsgICAgICAgICAgICAgICAgbWF4LXdpZHRoOjYwMHB4OyAgICAgICAgICAgIH0gICAgICAgICAgICAvKiA9PT09PT09PT09IEJvZHkgU3R5bGVzID09PT09PT09PT0gKi8gICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgQm9keSAgICAgICAgICAgICogQHNlY3Rpb24gYm9keSBzdHlsZSAgICAgICAgICAgICogQHRpcCBTZXQgdGhlIGJhY2tncm91bmQgY29sb3IgYW5kIGJvcmRlcnMgZm9yIHlvdXIgZW1haWwncyBib2R5IGFyZWEuI";$tpl.="CAgICAgICAgICAgKi8gICAgICAgICAgICAjdGVtcGxhdGVCb2R5eyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGJhY2tncm91bmQtY29sb3I6I0Y0RjRGNDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBib3JkZXItdG9wOjFweCBzb2xpZCAjRkZGRkZGOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGJvcmRlci1ib3R0b206MXB4IHNvbGlkICNDQ0NDQ0M7ICAgICAgICAgICAgfSAgICAgICAgICAgIC8qKiAgICAgICAgICAgICogQHRhYiBCb2R5ICAgICAgICAgICAgKiBAc2VjdGlvbiBib2R5IHRleHQgICAgICAgICAgICAqIEB0aXAgU2V0IHRoZSBzdHlsaW5nIGZvciB5b3VyIGVtYWlsJ3MgbWFpbiBjb250ZW50IHRleHQuIENob29zZSBhIHNpemUgYW5kIGNvbG9yIHRoYXQgaXMgZWFzeSB0byByZWFkLiAgICAgICAgICAgICogQHRoZW1lIG1haW4gICAgICAgICAgICAqLyAgICAgICAgICAgIC5ib2R5Q29udGVudHsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBjb2xvcjojNTA1MDUwOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtZmFtaWx5OkhlbHZldGljYTsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXNpemU6MTRweDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBsaW5lLWhlaWdodDoxNTAlOyAgICAgICAgICAgICAgICBwYWRkaW5nLXRvcDoyMHB4OyAgICAgICAgICAgICAgICBwYWRkaW5nLXJpZ2h0OjIwcHg7ICAgICAgICAgICAgICAgIHBhZGRpbmctYm";$tpl.="90dG9tOjIwcHg7ICAgICAgICAgICAgICAgIHBhZGRpbmctbGVmdDoyMHB4OyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIHRleHQtYWxpZ246bGVmdDsgICAgICAgICAgICB9ICAgICAgICAgICAgLyoqICAgICAgICAgICAgKiBAdGFiIEJvZHkgICAgICAgICAgICAqIEBzZWN0aW9uIGJvZHkgbGluayAgICAgICAgICAgICogQHRpcCBTZXQgdGhlIHN0eWxpbmcgZm9yIHlvdXIgZW1haWwncyBtYWluIGNvbnRlbnQgbGlua3MuIENob29zZSBhIGNvbG9yIHRoYXQgaGVscHMgdGhlbSBzdGFuZCBvdXQgZnJvbSB5b3VyIHRleHQuICAgICAgICAgICAgKi8gICAgICAgICAgICAuYm9keUNvbnRlbnQgYTpsaW5rLCAuYm9keUNvbnRlbnQgYTp2aXNpdGVkLCAvKiBZYWhvbyEgTWFpbCBPdmVycmlkZSAqLyAuYm9keUNvbnRlbnQgYSAueXNob3J0Y3V0cyAvKiBZYWhvbyEgTWFpbCBPdmVycmlkZSAqL3sgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBjb2xvcjojRUI0MTAyOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtd2VpZ2h0Om5vcm1hbDsgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyB0ZXh0LWRlY29yYXRpb246dW5kZXJsaW5lOyAgICAgICAgICAgIH0gICAgICAgICAgICAuYm9keUNvbnRlbnQgaW1neyAgICAgICAgICAgICAgICBkaXNwbGF5OmlubGluZTsgICAgICAgICAgICAgICAgaGVpZ2h0OmF1dG87ICAgICAgICAgICAgICAgIG1heC13aWR0aDo1NjBweDsgICAgICAgICAgICB9ICAgICAgICAgICA";$tpl.="gLyogPT09PT09PT09PSBGb290ZXIgU3R5bGVzID09PT09PT09PT0gKi8gICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgRm9vdGVyICAgICAgICAgICAgKiBAc2VjdGlvbiBmb290ZXIgc3R5bGUgICAgICAgICAgICAqIEB0aXAgU2V0IHRoZSBiYWNrZ3JvdW5kIGNvbG9yIGFuZCBib3JkZXJzIGZvciB5b3VyIGVtYWlsJ3MgZm9vdGVyIGFyZWEuICAgICAgICAgICAgKiBAdGhlbWUgZm9vdGVyICAgICAgICAgICAgKi8gICAgICAgICAgICAjdGVtcGxhdGVGb290ZXJ7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gYmFja2dyb3VuZC1jb2xvcjojRjRGNEY0OyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGJvcmRlci10b3A6MXB4IHNvbGlkICNGRkZGRkY7ICAgICAgICAgICAgfSAgICAgICAgICAgIC8qKiAgICAgICAgICAgICogQHRhYiBGb290ZXIgICAgICAgICAgICAqIEBzZWN0aW9uIGZvb3RlciB0ZXh0ICAgICAgICAgICAgKiBAdGlwIFNldCB0aGUgc3R5bGluZyBmb3IgeW91ciBlbWFpbCdzIGZvb3RlciB0ZXh0LiBDaG9vc2UgYSBzaXplIGFuZCBjb2xvciB0aGF0IGlzIGVhc3kgdG8gcmVhZC4gICAgICAgICAgICAqIEB0aGVtZSBmb290ZXIgICAgICAgICAgICAqLyAgICAgICAgICAgIC5mb290ZXJDb250ZW50ewkvKkBlZGl0YWJsZSovIGNvbG9yOiM4MDgwODA7CS8qQGVkaXRhYmxlKi8gZm9udC1mYW1pbHk6SGVsdmV0aWNhOwkvKkBlZGl0YWJsZSovIGZvbnQtc2l6ZToxMHB4OwkvKkBlZGl0YWJsZSov";$tpl.="IGxpbmUtaGVpZ2h0OjE1MCU7CXBhZGRpbmctdG9wOjJweDsJcGFkZGluZy1yaWdodDoyMHB4OwlwYWRkaW5nLWJvdHRvbToycHg7CXBhZGRpbmctbGVmdDoyMHB4OwkvKkBlZGl0YWJsZSovIHRleHQtYWxpZ246Y2VudGVyOyAgICAgICAgICAgIH0gICAgICAgICAgICAvKiogICAgICAgICAgICAqIEB0YWIgRm9vdGVyICAgICAgICAgICAgKiBAc2VjdGlvbiBmb290ZXIgbGluayAgICAgICAgICAgICogQHRpcCBTZXQgdGhlIHN0eWxpbmcgZm9yIHlvdXIgZW1haWwncyBmb290ZXIgbGlua3MuIENob29zZSBhIGNvbG9yIHRoYXQgaGVscHMgdGhlbSBzdGFuZCBvdXQgZnJvbSB5b3VyIHRleHQuICAgICAgICAgICAgKi8gICAgICAgICAgICAuZm9vdGVyQ29udGVudCBhOmxpbmssIC5mb290ZXJDb250ZW50IGE6dmlzaXRlZCwgLyogWWFob28hIE1haWwgT3ZlcnJpZGUgKi8gLmZvb3RlckNvbnRlbnQgYSAueXNob3J0Y3V0cywgLmZvb3RlckNvbnRlbnQgYSBzcGFuIC8qIFlhaG9vISBNYWlsIE92ZXJyaWRlICoveyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGNvbG9yOiM2MDYwNjA7ICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gZm9udC13ZWlnaHQ6bm9ybWFsOyAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIHRleHQtZGVjb3JhdGlvbjp1bmRlcmxpbmU7ICAgICAgICAgICAgfSAgICAgICAgICAgIC8qIC9cL1wvXC9cL1wvXC9cL1wvIE1PQklMRSBTVFlMRVMgL1wvXC9cL1wvXC9cL1wvXC8gKi8gICAgICAgICAgICBAb";$tpl.="WVkaWEgb25seSBzY3JlZW4gYW5kIChtYXgtd2lkdGg6IDQ4MHB4KXsgICAgICAgICAgICAgICAgLyogL1wvXC9cL1wvXC9cLyBDTElFTlQtU1BFQ0lGSUMgTU9CSUxFIFNUWUxFUyAvXC9cL1wvXC9cL1wvICovICAgICAgICAgICAgICAgIGJvZHksIHRhYmxlLCB0ZCwgcCwgYSwgbGksIGJsb2NrcXVvdGV7LXdlYmtpdC10ZXh0LXNpemUtYWRqdXN0Om5vbmUgIWltcG9ydGFudDt9IC8qIFByZXZlbnQgV2Via2l0IHBsYXRmb3JtcyBmcm9tIGNoYW5naW5nIGRlZmF1bHQgdGV4dCBzaXplcyAqLyAgICAgICAgICAgICAgICBib2R5e3dpZHRoOjEwMCUgIWltcG9ydGFudDsgbWluLXdpZHRoOjEwMCUgIWltcG9ydGFudDt9IC8qIFByZXZlbnQgaU9TIE1haWwgZnJvbSBhZGRpbmcgcGFkZGluZyB0byB0aGUgYm9keSAqLyAgICAgICAgICAgICAgICAvKiAvXC9cL1wvXC9cL1wvIE1PQklMRSBSRVNFVCBTVFlMRVMgL1wvXC9cL1wvXC9cLyAqLyAgICAgICAgICAgICAgICAjYm9keUNlbGx7cGFkZGluZzoxMHB4ICFpbXBvcnRhbnQ7fSAgICAgICAgICAgICAgICAvKiAvXC9cL1wvXC9cL1wvIE1PQklMRSBURU1QTEFURSBTVFlMRVMgL1wvXC9cL1wvXC9cLyAqLyAgICAgICAgICAgICAgICAvKiA9PT09PT09PSBQYWdlIFN0eWxlcyA9PT09PT09PSAqLyAgICAgICAgICAgICAgICAvKiogICAgICAgICAgICAgICAgKiBAdGFiIE1vYmlsZSBTdHlsZXMgICAgICAgICAgICAgICAgKiBAc2VjdGlvbiB0ZW1wbGF0ZSB3aWR0aCAgICAgICAgICAgICAgIC";$tpl.="AqIEB0aXAgTWFrZSB0aGUgdGVtcGxhdGUgZmx1aWQgZm9yIHBvcnRyYWl0IG9yIGxhbmRzY2FwZSB2aWV3IGFkYXB0YWJpbGl0eS4gSWYgYSBmbHVpZCBsYXlvdXQgZG9lc24ndCB3b3JrIGZvciB5b3UsIHNldCB0aGUgd2lkdGggdG8gMzAwcHggaW5zdGVhZC4gICAgICAgICAgICAgICAgKi8gICAgICAgICAgICAgICAgI3RlbXBsYXRlQ29udGFpbmVyeyAgICAgICAgICAgICAgICAgICAgbWF4LXdpZHRoOjYwMHB4ICFpbXBvcnRhbnQ7ICAgICAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIHdpZHRoOjEwMCUgIWltcG9ydGFudDsgICAgICAgICAgICAgICAgfSAgICAgICAgICAgICAgICAvKiogICAgICAgICAgICAgICAgKiBAdGFiIE1vYmlsZSBTdHlsZXMgICAgICAgICAgICAgICAgKiBAc2VjdGlvbiBoZWFkaW5nIDEgICAgICAgICAgICAgICAgKiBAdGlwIE1ha2UgdGhlIGZpcnN0LWxldmVsIGhlYWRpbmdzIGxhcmdlciBpbiBzaXplIGZvciBiZXR0ZXIgcmVhZGFiaWxpdHkgb24gc21hbGwgc2NyZWVucy4gICAgICAgICAgICAgICAgKi8gICAgICAgICAgICAgICAgaDF7ICAgICAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtc2l6ZToyNHB4ICFpbXBvcnRhbnQ7ICAgICAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGxpbmUtaGVpZ2h0OjEwMCUgIWltcG9ydGFudDsgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSAgICAgICAgICAgICAgICAvKiogICAgICAgICAgICAgICAgKiBAdGFiIE1vYml";$tpl.="sZSBTdHlsZXMgICAgICAgICAgICAgICAgKiBAc2VjdGlvbiBoZWFkaW5nIDIgICAgICAgICAgICAgICAgKiBAdGlwIE1ha2UgdGhlIHNlY29uZC1sZXZlbCBoZWFkaW5ncyBsYXJnZXIgaW4gc2l6ZSBmb3IgYmV0dGVyIHJlYWRhYmlsaXR5IG9uIHNtYWxsIHNjcmVlbnMuICAgICAgICAgICAgICAgICovICAgICAgICAgICAgICAgIGgyeyAgICAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXNpemU6MjBweCAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBsaW5lLWhlaWdodDoxMDAlICFpbXBvcnRhbnQ7ICAgICAgICAgICAgICAgIH0gICAgICAgICAgICAgICAgLyoqICAgICAgICAgICAgICAgICogQHRhYiBNb2JpbGUgU3R5bGVzICAgICAgICAgICAgICAgICogQHNlY3Rpb24gaGVhZGluZyAzICAgICAgICAgICAgICAgICogQHRpcCBNYWtlIHRoZSB0aGlyZC1sZXZlbCBoZWFkaW5ncyBsYXJnZXIgaW4gc2l6ZSBmb3IgYmV0dGVyIHJlYWRhYmlsaXR5IG9uIHNtYWxsIHNjcmVlbnMuICAgICAgICAgICAgICAgICovICAgICAgICAgICAgICAgIGgzeyAgICAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXNpemU6MThweCAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBsaW5lLWhlaWdodDoxMDAlICFpbXBvcnRhbnQ7ICAgICAgICAgICAgICAgIH0gICAgICAgICAgICAgICAgLyoqICAgICAgICAgICAgICAgICogQHRhYiBNb2JpbGUgU3R5bGVz";$tpl.="ICAgICAgICAgICAgICAgICogQHNlY3Rpb24gaGVhZGluZyA0ICAgICAgICAgICAgICAgICogQHRpcCBNYWtlIHRoZSBmb3VydGgtbGV2ZWwgaGVhZGluZ3MgbGFyZ2VyIGluIHNpemUgZm9yIGJldHRlciByZWFkYWJpbGl0eSBvbiBzbWFsbCBzY3JlZW5zLiAgICAgICAgICAgICAgICAqLyAgICAgICAgICAgICAgICBoNHsgICAgICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gZm9udC1zaXplOjE2cHggIWltcG9ydGFudDsgICAgICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gbGluZS1oZWlnaHQ6MTAwJSAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICB9ICAgICAgICAgICAgICAgIC8qID09PT09PT09IEhlYWRlciBTdHlsZXMgPT09PT09PT0gKi8gICAgICAgICAgICAgICAgI3RlbXBsYXRlUHJlaGVhZGVye2Rpc3BsYXk6bm9uZSAhaW1wb3J0YW50O30gLyogSGlkZSB0aGUgdGVtcGxhdGUgcHJlaGVhZGVyIHRvIHNhdmUgc3BhY2UgKi8gICAgICAgICAgICAgICAgLyoqICAgICAgICAgICAgICAgICogQHRhYiBNb2JpbGUgU3R5bGVzICAgICAgICAgICAgICAgICogQHNlY3Rpb24gaGVhZGVyIGltYWdlICAgICAgICAgICAgICAgICogQHRpcCBNYWtlIHRoZSBtYWluIGhlYWRlciBpbWFnZSBmbHVpZCBmb3IgcG9ydHJhaXQgb3IgbGFuZHNjYXBlIHZpZXcgYWRhcHRhYmlsaXR5LCBhbmQgc2V0IHRoZSBpbWFnZSdzIG9yaWdpbmFsIHdpZHRoIGFzIHRoZSBtYXgtd2lkdGguIElmIGEgZmx1aWQgc2V0dGluZyBkb2Vzbid0IHdvc";$tpl.="mssIHNldCB0aGUgaW1hZ2Ugd2lkdGggdG8gaGFsZiBpdHMgb3JpZ2luYWwgc2l6ZSBpbnN0ZWFkLiAgICAgICAgICAgICAgICAqLyAgICAgICAgICAgICAgICAjaGVhZGVySW1hZ2V7ICAgICAgICAgICAgICAgICAgICBoZWlnaHQ6YXV0byAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBtYXgtd2lkdGg6NjAwcHggIWltcG9ydGFudDsgICAgICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gd2lkdGg6MTAwJSAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICB9ICAgICAgICAgICAgICAgIC8qKiAgICAgICAgICAgICAgICAqIEB0YWIgTW9iaWxlIFN0eWxlcyAgICAgICAgICAgICAgICAqIEBzZWN0aW9uIGhlYWRlciB0ZXh0ICAgICAgICAgICAgICAgICogQHRpcCBNYWtlIHRoZSBoZWFkZXIgY29udGVudCB0ZXh0IGxhcmdlciBpbiBzaXplIGZvciBiZXR0ZXIgcmVhZGFiaWxpdHkgb24gc21hbGwgc2NyZWVucy4gV2UgcmVjb21tZW5kIGEgZm9udCBzaXplIG9mIGF0IGxlYXN0IDE2cHguICAgICAgICAgICAgICAgICovICAgICAgICAgICAgICAgIC5oZWFkZXJDb250ZW50eyAgICAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXNpemU6MjBweCAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBsaW5lLWhlaWdodDoxMjUlICFpbXBvcnRhbnQ7ICAgICAgICAgICAgICAgIH0gICAgICAgICAgICAgICAgLyoqICAgICAgICAgICAgICAgICogQHRhYiBNb2JpbG";$tpl.="UgU3R5bGVzICAgICAgICAgICAgICAgICogQHNlY3Rpb24gaGVhZGVyIHRleHQgICAgICAgICAgICAgICAgKiBAdGlwIE1ha2UgdGhlIGhlYWRlciBjb250ZW50IHRleHQgbGFyZ2VyIGluIHNpemUgZm9yIGJldHRlciByZWFkYWJpbGl0eSBvbiBzbWFsbCBzY3JlZW5zLiBXZSByZWNvbW1lbmQgYSBmb250IHNpemUgb2YgYXQgbGVhc3QgMTZweC4gICAgICAgICAgICAgICAgKi8gICAgICAgICAgICAgICAgLmhlYWRlckNvbnRlbnRJbmZveyAgICAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBmb250LXNpemU6MjBweCAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICAgICAgLypAZWRpdGFibGUqLyBsaW5lLWhlaWdodDoxMjUlICFpbXBvcnRhbnQ7ICAgICAgICAgICAgICAgICAgICB0ZXh0LWFsaWduOiByaWdodDsgICAgICAgICAgICAgICAgfSAgICAgICAgICAgICAgICAvKiA9PT09PT09PSBCb2R5IFN0eWxlcyA9PT09PT09PSAqLyAgICAgICAgICAgICAgICAvKiogICAgICAgICAgICAgICAgKiBAdGFiIE1vYmlsZSBTdHlsZXMgICAgICAgICAgICAgICAgKiBAc2VjdGlvbiBib2R5IHRleHQgICAgICAgICAgICAgICAgKiBAdGlwIE1ha2UgdGhlIGJvZHkgY29udGVudCB0ZXh0IGxhcmdlciBpbiBzaXplIGZvciBiZXR0ZXIgcmVhZGFiaWxpdHkgb24gc21hbGwgc2NyZWVucy4gV2UgcmVjb21tZW5kIGEgZm9udCBzaXplIG9mIGF0IGxlYXN0IDE2cHguICAgICAgICAgICAgICAgICovICAgICAgICAgICAgICAgIC5ib2R5Q29udGV";$tpl.="udHsgICAgICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gZm9udC1zaXplOjE4cHggIWltcG9ydGFudDsgICAgICAgICAgICAgICAgICAgIC8qQGVkaXRhYmxlKi8gbGluZS1oZWlnaHQ6MTI1JSAhaW1wb3J0YW50OyAgICAgICAgICAgICAgICB9ICAgICAgICAgICAgICAgIC8qID09PT09PT09IEZvb3RlciBTdHlsZXMgPT09PT09PT0gKi8gICAgICAgICAgICAgICAgLyoqICAgICAgICAgICAgICAgICogQHRhYiBNb2JpbGUgU3R5bGVzICAgICAgICAgICAgICAgICogQHNlY3Rpb24gZm9vdGVyIHRleHQgICAgICAgICAgICAgICAgKiBAdGlwIE1ha2UgdGhlIGJvZHkgY29udGVudCB0ZXh0IGxhcmdlciBpbiBzaXplIGZvciBiZXR0ZXIgcmVhZGFiaWxpdHkgb24gc21hbGwgc2NyZWVucy4gICAgICAgICAgICAgICAgKi8gICAgICAgICAgICAgICAgLmZvb3RlckNvbnRlbnR7ICAgICAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGZvbnQtc2l6ZToxNHB4ICFpbXBvcnRhbnQ7ICAgICAgICAgICAgICAgICAgICAvKkBlZGl0YWJsZSovIGxpbmUtaGVpZ2h0OjExNSUgIWltcG9ydGFudDsgICAgICAgICAgICAgICAgfSAgICAgICAgICAgICAgICAuZm9vdGVyQ29udGVudCBhe2Rpc3BsYXk6YmxvY2sgIWltcG9ydGFudDt9IC8qIFBsYWNlIGZvb3RlciBzb2NpYWwgYW5kIHV0aWxpdHkgbGlua3Mgb24gdGhlaXIgb3duIGxpbmVzLCBmb3IgZWFzaWVyIGFjY2VzcyAqLyAgICAgICAgICAgIH0gICAgICAgIDwvc3R5bGU+ICAgIDwvaGVh";$tpl.="ZD4gICAgPGJvZHkgbGVmdG1hcmdpbj0iMCIgbWFyZ2lud2lkdGg9IjAiIHRvcG1hcmdpbj0iMCIgbWFyZ2luaGVpZ2h0PSIwIiBvZmZzZXQ9IjAiPiAgICAgICAgPGNlbnRlcj4gICAgICAgICAgICA8dGFibGUgYWxpZ249ImNlbnRlciIgYm9yZGVyPSIwIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGhlaWdodD0iMTAwJSIgd2lkdGg9IjEwMCUiIGlkPSJib2R5VGFibGUiPiAgICAgICAgICAgICAgICA8dHI+ICAgICAgICAgICAgICAgICAgICA8dGQgYWxpZ249ImNlbnRlciIgdmFsaWduPSJ0b3AiIGlkPSJib2R5Q2VsbCI+ICAgICAgICAgICAgICAgICAgICAgICAgPCEtLSBCRUdJTiBURU1QTEFURSAvLyAtLT4gICAgICAgICAgICAgICAgICAgICAgICA8dGFibGUgYm9yZGVyPSIwIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGlkPSJ0ZW1wbGF0ZUNvbnRhaW5lciI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0cj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZCBhbGlnbj0iY2VudGVyIiB2YWxpZ249InRvcCI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPCEtLSBCRUdJTiBQUkVIRUFERVIgLy8gLS0+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRhYmxlIGJvcmRlcj0iMCIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiB3aWR0aD0iMTAwJSIgaWQ9InRlbXBsYXRlUHJlaGVhZGVyIj4gICAgICAgICAgICAgICAgICAgICAgICAgI";$tpl.="CAgICAgICAgICA8L3RhYmxlPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwhLS0gLy8gRU5EIFBSRUhFQURFUiAtLT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGQ+ICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdHI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0cj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZCBhbGlnbj0iY2VudGVyIiB2YWxpZ249InRvcCI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPCEtLSBCRUdJTiBIRUFERVIgLy8gLS0+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRhYmxlIGJvcmRlcj0iMCIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiB3aWR0aD0iMTAwJSIgaWQ9InRlbXBsYXRlSGVhZGVyIj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRyPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRkIHZhbGlnbj0idG9wIiBjbGFzcz0iaGVhZGVyQ29udGVudCI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRhYmxlIGJvcmRlcj0iMCIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiB3aWR0aD0iMTAwJSI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0cj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC";$tpl.="AgICAgICAgICAgICAgIDx0ZCB3aWR0aD0iMTAlIiBoZWlnaHQ9IjEyOSIgdmFsaWduPSJ0b3AiIGNsYXNzPSJoZWFkZXJMb2dvIj48aW1nIHNyYz0naHR0cHM6Ly9zY2h1bGRuZXJjZW50ZXIuaW5rYXNzby12b3AuZGUvaW1hZ2VzL3Z2di5wbmcnPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8YnIgLz48L3RkPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRkIHdpZHRoPSI5MCUiIHZhbGlnbj0idG9wIiBjbGFzcz0iaGVhZGVyU3VtbWFyeSI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGFibGUgd2lkdGg9IjEwMCUiPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRyPjx0ZCB3aWR0aD0iNTMlIj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aDE+Vi5PLlAgRmluYW56ZGllbnN0bGVpc3R1bmc8c3BhbiBzdHlsZT0iY29sb3I6I0EwQTBBNCI+PC9zcGFuPjwvaDE+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDxkaXYgc3R5bGU9J2NvbG9yOiNBMEEwQTQnPlphaGx1bmdzZXJpbm5lcnVuZzogPGVtPiRSZWNobnV";$tpl.="uZ3NOciQ8L2VtPjwvZGl2PiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RkPjx0ZCBhbGlnbj0icmlnaHQiIHdpZHRoPSI0NyUiPjxpbWcgd2lkdGg9MzAzIGhlaWdodD05OCBhbGlnbj0ncmlnaHQnICBzcmM9J2h0dHBzOi8vc2NodWxkbmVyY2VudGVyLmlua2Fzc28tdm9wLmRlL2ltYWdlcy9jdXN0b20vZ3JhdV9ibGFjay5QTkcnIC8+PC90ZD4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90cj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90YWJsZT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90ZD4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90cj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGFibGU+PC90ZD4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZCB2YWxpZ249InRvcCIgY2xhc3M9ImhlYWRlckNvbnRlbnRJbmZvIj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGQ+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdHI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90YWJsZT4gICAgICAgICAgICAgICAgICAgICAg";$tpl.="ICAgICAgICAgICAgICA8IS0tIC8vIEVORCBIRUFERVIgLS0+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RkPiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RyPiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dHI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGQgYWxpZ249ImNlbnRlciIgdmFsaWduPSJ0b3AiPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwhLS0gQkVHSU4gQk9EWSAvLyAtLT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGFibGUgYm9yZGVyPSIwIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIHdpZHRoPSIxMDAlIiBpZD0idGVtcGxhdGVCb2R5Ij4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRyPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRkIHZhbGlnbj0idG9wIiBjbGFzcz0iYm9keUNvbnRlbnQiPlphaGx1bmdzZXJpbm5lcnVuZyAkRmlybWEkPGJyPjxicj48Yj5SZWNobnVuZyBOcjogJFJlY2hudW5nc05yJCAgdm9tICRSZWNobnVuZ3NkYXR1bSQ8L2I+IDxicj48YnI+JEFucmVkZSQsPGJyPmhhYmVuIFNpZSBkaWUgUmVjaG51bmcgbmljaHQgZXJoYWx0ZW4gb2RlciBkaWVzZSB2aWVsbGVpY2h0IGVpbmZhY2ggbnVyIHZlcmdlc3Nlbj88YnI+PGJyPk5hbWVucyB1bmQgaW0gQXVmdHJhZyB2b24gPGI+JEZpcm1hJDwvYj4gbcO2Y2h0ZW4gd2lyIFNpZ";$tpl.="SBkYXJhbiBlcmlubmVybiwgZGFzcyBkaWUgby5hIFJlY2hudW5nIG1pdCBkZW0gbm9jaCBvZmZlbmVuIEdlc2FtdGJldHJhZyB2b24gPGJyPjxicj48ZGl2IHN0eWxlPSdtYXJnaW4tbGVmdDoyMDBweDsnPjxiPiRCZXRyYWdvZmZlbiQ8L2I+IDwvZGl2Pjxicj5ub2NoIG5pY2h0IGF1c2dlZ2xpY2hlbiB3dXJkZS48YnI+PGJyPlNvbGx0ZW4gU2llIGRpZSBSZWNobnVuZyBub2NoIG5pY2h0IGVyaGFsdGVuIGhhYmVuLCBzbyBmaW5kZW4gU2llIGRpZXNlIGltIEFuaGFuZyBkaWVzZXIgZU1haWwuPGJyPlNvbGx0ZSBzaWNoIGRpZXNlIE5hY2hyaWNodCBtaXQgSWhyZXIgZ2V0w6R0aWd0ZW4gWmFobHVuZyDDvGJlcnNjaG5pdHRlbiBoYWJlbiwgc28gYmV0cmFjaHRlbiBTaWUgZGllc2UgZU1haWwgYWxzIGdlZ2Vuc3RhbmRzbG9zLjxicj48YnI+QmVpIEZyYWdlbiB6dSBkaWVzZXIgWmFobHVuZ3NlcmlubmVydW5nIHdlbmRlbiBTaWUgc2ljaCBiaXR0ZSBhbiB1bnNlcmVuIEF1ZnRyYWdnZWJlciA8YnI+PGI+UsO8Y2ttZWxkdW5nZW4gYW4gdW5zZXJlIEFkcmVzc2UgemFobHVuZ3NlcmlubmVydW5nQHZvcG9ubGluZS5kZSB3ZXJkZW4gbmljaHQgenVnZXN0ZWxsdC48L2I+PGJyPjxicj5NaXQgZnJldW5kbGljaGVuIEdyw7zDn2VuPGJyPklocmUgVi5PLlAgIDxicj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGQ+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdHI+ICAgIC";$tpl.="AgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90YWJsZT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8IS0tIEVORCBCT0RZIC8vIC0tPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90ZD4gICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90cj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRyPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRkIGFsaWduPSJjZW50ZXIiIHZhbGlnbj0idG9wIj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8IS0tIEJFR0lOIEZPT1RFUiAvLyAtLT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGFibGUgYm9yZGVyPSIwIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIHdpZHRoPSIxMDAlIiBpZD0idGVtcGxhdGVGb290ZXIiPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dHI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGQgdmFsaWduPSJ0b3AiIGNsYXNzPSJmb290ZXJDb250ZW50Ij4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA";$tpl.="gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGQ+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdHI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0cj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZCB2YWxpZ249InRvcCIgY2xhc3M9ImZvb3RlckNvbnRlbnQiIHN0eWxlPSJwYWRkaW5nLXRvcDowOyI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRhYmxlIGJvcmRlcj0iMCIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiB3aWR0aD0iMTAwJSIgaWQ9ImZvb3RlckluZm8iPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0cj4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRkIHZhbGlnbj0idG9wIiAgY2xhc3M9ImZvb3RlckNvbnRlbnQiIHdpZHRoPSIzMyUiPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGVtPlYuTy5QIEdtYkggJmFtcDsgQ28uIEtHPC9lbT48YnIvPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgSGF1cHRzdHJhc3NlLiA2MjxiciAvPiAgICAgICAgICAg";$tpl.="ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgNTY3NDUgLyBCZWxsPGJyIC8+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8YSBocmVmPSdodHRwOi8vd3d3LnZvcG9ubGluZS5kZSc+d3d3Lmlua2Fzc28tdm9wLmRlPC9hPjxiciAvPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RkPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdHI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90YWJsZT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGQ+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdHI+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90YWJsZT4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8IS0tIC8vIEVORCBGT09URVIgLS0+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RkPiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RyPiAgICAgICAgICAgICAgICAgICAgICAgIDwvdGFibGU+ICAgICAgICAgICAgICAgICAgICAgICAgPCEtLSAvLyBFTkQgVEVNUExBVEUgLS0+ICAgICAgICAgICAgICAgICAgI";$tpl.="CA8L3RkPiAgICAgICAgICAgICAgICA8L3RyPiAgICAgICAgICAgIDwvdGFibGU+ICAgICAgICA8L2NlbnRlcj4gICAgPC9ib2R5PjwvaHRtbD4=";
return $tpl;
}
}
?>