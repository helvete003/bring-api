<?php
/*
*   Incomplete, reverse-engineered and unofficial Bring! API
*/

class BringApi {
  const GET_REQUEST = 'get';
  const POST_REQUEST = 'post';
  const PUT_REQUEST = 'put';

  private $bringRestURL = "https://api.getbring.com/rest/";
  private $bringUUID = "";
  private $bringListUUID = "";

  private $answerHttpStatus = -1;

  /**
  *   It is recommended to use the UUID/listUUID method to save one request to the getbring server
  *
  *   @param string $UUID       Should contain a UUID or the email of the user
  *   @param string $listUUID   Should contain a listUUID or a password of the user
  *   @param bool $useLogin     true if you want to use a email/password login combination
  */
  public function __construct($UUID,$listUUID, $useLogin = false)
  {
    if($useLogin) {
      $login = json_decode($this->login($UUID,$listUUID),true);
      if($this->answerHttpStatus == 200 && $login != "") {
        $this->bringUUID = $login['uuid'];
        $this->bringListUUID = $login['bringListUUID'];
      } else {
        die("Wrong Login!");
      }
    } else {
      $this->bringUUID = $UUID;
      $this->bringListUUID = $listUUID;
    }
  }

  /**
  *   @param string $email
  *   @param string $password
  */
  private function login($email,$password)
  {
    return $this->request(self::GET_REQUEST,"bringlists","?email=".$email."&password=".$password);
  }

  /**
  *   Get all items from the current selected shopping list
  *
  *   @return json string or html code
  */
  public function getItems()
  {
    return $this->request(self::GET_REQUEST,"bringlists/".$this->bringListUUID,"",true);
  }

  /**
  *   Save an item to your current shopping list
  *
  *   @param string $itemName       The name of the item you want to send to the bring server
  *   @param string $specification  The litte description under the name of the item
  *   @return should return an empty string and $answerHttpStatus should contain 204. If not -> error
  */
  public function saveItem($itemName,$specification)
  {
    return $this->request(self::PUT_REQUEST,"bringlists/".$this->bringListUUID,"purchase=".$itemName."&recently=&specification=".$specification."&remove=&sender=null",true);
  }

  /**
  *   remove an item from your current shopping list
  *
  *   @param string $itemName       Name of the item you want to delete from you shopping list
  *   @return should return an empty string and $answerHttpStatus should contain 204. If not -> error
  */
  public function removeItem($itemName)
  {
    return $this->request(self::PUT_REQUEST,"bringlists/".$this->bringListUUID, "purchase=&recently=&specification=&remove=".$itemName."&sender=null",true);
  }

  /**
  *   Search for an item
  *
  *   @param string $search   The item you want to search
  *   @return json string or html code
  */
  public function searchItem($search)
  {
    return $this->request(self::GET_REQUEST,"bringlistitemdetails/", "?listUuid=".$this->bringListUUID."&itemId=".$search,true);
  }

  // Hidden Icons? Don't know what this is used for
  public function loadProducts()
  {
    return $this->request(self::GET_REQUEST,"bringproducts", "",true);
  }

  // Found Icons? Don't know what this is used for
  public function loadFeatures()
  {
    return $this->request(self::GET_REQUEST,"bringusers/".$this->bringUUID."/features", "",true);
  }

  /**
  *   Loads all shopping lists
  *
  *   @return json string or html code
  */
  public function loadLists()
  {
    return $this->request(self::GET_REQUEST,"bringusers/".$this->bringUUID."/lists", "",true);
  }

  /**
  *   Get all users from a shopping list
  *
  *   @param string $listUUID   The lisUUID you want to recive a list of users from.
  *   @return json string or html code
  */
  public function getAllUsersFromList($listUUID)
  {
    return $this->request(self::GET_REQUEST,"bringlists/".$listUUID."/users", "",true);
  }

  /**
  *   @return json string or html code
  */
  public function getUserSettings()
  {
    return $this->request(self::GET_REQUEST,"bringusersettings/".$this->bringUUID, "",true);
  }

  /**
  *   @return int containing the http status code from the answer
  */
  public function getHttpStatus()
  {
    return $this->answerHttpStatus;
  }

  /**
  *   Handles the request to the server
  *
  *   @param const string $type   The HTTP request type.
  *   @param string $request      contains the request URL
  *   @param string $parameter    The parameters we send with the request
  *   @param bool $customHeader   True if you want to send the custom header (That is necessary because it sends the API-KEY) with the request
  *   @return The answer string from the server
  */
  private function request($type = self::GET_REQUEST,$request, $parameter, $customHeader = false)
  {
    $ch = curl_init();
    $additionalHeaderInfo = "";
    switch($type) {
      case self::GET_REQUEST:
        curl_setopt($ch, CURLOPT_URL, $this->bringRestURL.$request.$parameter);
      break;
      case self::POST_REQUEST:
        curl_setopt($ch, CURLOPT_URL, $this->bringRestURL.$request);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$parameter);
      break;
      case self::PUT_REQUEST:
        $fh = tmpfile();
        fwrite($fh, $parameter);
        fseek($fh, 0);
        curl_setopt($ch, CURLOPT_URL, $this->bringRestURL.$request);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, strlen($parameter));
        $additionalHeaderInfo = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
      break;
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if($customHeader) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader(($additionalHeaderInfo != "")?$additionalHeaderInfo:null));
    }
    $server_output = curl_exec ($ch);
    $this->answerHttpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);

    return $server_output;
  }
  /**
  *   @param string|array $additional   additional field that you want to add to the header
  *   @return array with the headerinformation
  */
  private function getHeader($additional = null)
  {
    $header = [
      'X-BRING-API-KEY: cof4Nc6D8saplXjE3h3HXqHH8m7VU2i1Gs0g85Sp',
      'X-BRING-CLIENT: android',
      'X-BRING-USER-UUID: '.$this->bringUUID,
      'X-BRING-VERSION: 303070050',
      'X-BRING-COUNTRY: de',
    ];
    if($additional != null) {
      if(is_array($additional)) {
        foreach($additional as $key => $value) {
          $header[] = $value;
        }
      } else {
        $header[] = $additional;
      }
    }
    return $header;
  }
}

?>
