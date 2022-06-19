<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use http\Client\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class HomeController extends Controller
{

    public function getRecordFromApi(){
        $result = array();//initialization of empty array to store the record

        foreach (range(1, 10) as $i) { //since to make 10 request and to get 10 record
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://randomuser.me/api',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $decodedResult = json_decode($response);

            if(!empty($decodedResult->results[0])) {//checking if the result that got from the api is empty or not
                $extractedResult = array(
                    'first' => $decodedResult->results[0]->name->first,
                    'last' => $decodedResult->results[0]->name->last,
                    'phone' => $decodedResult->results[0]->phone,
                    'email' => $decodedResult->results[0]->email,
                );
                array_push($result, $extractedResult);
            }
        }
        $this->sortByLastName($result); //to sort the result by the last name
    }

    public function sortByLastName($result){
        usort($result, function($a, $b)
        {
            return strcmp($b['last'], $a['last']);
        });
        Storage::put(config('constants.filePath').config('constants.fileName'), $this->array2XML($result));//making file of the record after sorting and converting to xml format
        echo "<h1>Public Api to get record converted to xml: {baseUrl}/api/converted/xml</h1>";
    }

    function array2XML($data){ //to covert the array of record to xml format
        $xml_data = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data></data>');
        foreach( $data as $key => $value ) {
            $node = $xml_data->addChild('record_' . $key);

            foreach( $value as $keyx => $valuex ) {
                $node->addChild($keyx,$valuex);
            }

        }
        return $xml_data->asXML();
    }

    public function getConvertedXMLResponse(Request $request){//get the converted record that is sorted in the file and sending it as a json response

        if(!Storage::exists(config('constants.filePath').config('constants.fileName'))){
            $this->getRecordFromApi();
        }

        $xmlString = file_get_contents(storage_path(config('constants.storagePath').config('constants.fileName')));

        $xmlObject = simplexml_load_string($xmlString);
        return response($xmlString, 200, [
            'Content-Type' => 'application/xml',
            'charset' => 'utf-8'
        ]);


    }
}
