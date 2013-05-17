<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 foldmethod=marker: */

/*
 * Gameserver Query Protocol Class
 * Copyright (C) 2004 Manuel Mausz ( manuel @ clanserver.eu )
 * ClanServer - Just Gaming!
 * http://www.clanserver.eu
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 */

/* {{{ Description
 * Gameserver Query Protocol Class
 *
 * Queries different gameservers and returns the current
 * status information. supports several query protocols.
 *
 * Supported Protocols:
 * For further information: http://www.int64.org/protocols/
 *   - Half-Life/Half-Life: Source ("HalfLife")
 *   - All Seeing Eye ("AllSeeingEye")
 *   - Quake 1/Quake World ("Quake1")
 *   - Quake 2 ("Quake2")
 *   - Quake 3 ("Quake3")
 *   - Doom 3/Quake 4/Enemy Territory: Quake Wars ("Doom3")
 *   - GameSpy/UT ("GameSpy")
 *   - GameSpy 2 ("GameSpy2")
 *   - GameSpy 3 ("GameSpy3")
 *   - GameSpy 4 ("GameSpy4")
 *
 * Users:
 *   $this->SetProtocol("protocol")
 *     ... protocol to use for query
 *
 *   $this->SetIpPort("ip:port:queryport")
 *     ... ip, port and queryport to use for query
 *         the queryport is optional
 *
 *   $this->SetLocalQuery(0 or 1)
 *     ... set localhost query (used for halflife query)
 *
 *   $this->SetRequestData(array("infotype1", "infotype2", ...))
 *     ... data to request from server. must be type array
 *         use the alias "FullInfo" for full information
 *
 *   array = $this->GetData()
 *     ... request the data from gameserver
 *         data will be returned as type array
 *
 *   bool = $this->ERROR
 *     ... TRUE if an error occur, FALSE otherwise
 *
 *   string = $this->ERRSTR
 *     ... contains the error message as string
 *
 * Advanced Users:
 *   $this->SetSocketTimeOut(int seconds, int microseconds)
 *     ... sets socket timeout. default: 0s, 50000ms
 *
 *   $this->SetSocketLoopTimeOut(int microseconds)
 *     ... sets loop timeout in microseconds. default: 1000
 *
 * Debug Modus:
 *   define("DEBUG", TRUE)
 *     ... enables debug modus
 *
 *  AutoDetection:
 *    $this->SetProtocol("AutoDetect")
 *      ... must be set to "AutoDetect"
 *    $this->SetProtocols(array("protocol1", "protocol2", ...))
 *      ... protocols to use for autodetection
 *      ... NOTE: GameSpyPortPlus10 is an alias for GameSpy but uses QueryPort+10
 *
 * Developers:
 *   This class contains an autodetect-query-protocol-engine
 *   If you want to add your own query, you will have to name your methodes like:
 *     _YourNameMain()       ... main methode. will be called first from engine
 *     _YourNameString1()    ... sub methode. must be called from main methode
 *                               will request string1 from gameserver (eg: _YourNameDetails)
 *     _YourNameAutoDetect() ... will be called from autodetection engine before _YourNameMain
 *                               normally used for setting correct query port
 *                               if you don't want autodetection, don't define this methode
 *     _YourNameFullInfo()   ... wrapper for requestdatatype "FullInfo"
 *
 }}} */

/* {{{ ToDo:
 *  - HalfLife/HalfLifeSource
 *    - Sometimes query comes in fragments prefixed by an id
 *      syntax: "<id>2" -> 02, 12, 22, ... (not sure about that)
 *      this behavior is only recognized in _HalfLifeRules()
 *      -> append fragments depending on their queryid
 *  - GameSpy
 *    - If one player name contains the string deliminater "\", the query will probably stop working
 *    - Protocol is fragmental and will send a queryid followed by a number at the end
 *      -> append fragments depending on their queryid
 *  - Better Error Handling!
 }}} */

class GSQuery
{
  // {{{ global variable definitions
  var $ERROR;
  var $ERRSTR;
  var $DEBUG;

  var $_ip;
  var $_port;
  var $_queryport;
  var $_fullinfo;
  var $_localquery;
  var $_socket;
  var $_stimeout;
  var $_mstimeout;
  var $_looptimeout;
  var $_protocol;
  var $_gettypes;
  var $_protocols;
  // }}}

  // {{{ GSQuery() - main class constructor
  function GSQuery()
  {
    $this->ERROR  = FALSE;
    $this->ERRSTR = "";
    $this->DEBUG  = FALSE;
    
    $this->_ip          = 0;
    $this->_port        = 0;
    $this->_queryport   = 0;
    $this->_fullinfo    = 0;
    $this->_localquery  = 0;
    $this->_socket      = 0;
    $this->_stimeout    = 0;
    $this->_mstimeout   = 50000;
    $this->_looptimeout = 1000;
    $this->_protocol    = "";
    $this->_gettypes    = array();
    $this->_protocols   = array();
    $this->_globalvars = array();

    if (defined('DEBUG') && DEBUG)
      $this->DEBUG = TRUE;
// Commented out to disable error messages
    set_error_handler(array(&$this, "_ErrorHandler"));
    return TRUE;
  }
  // }}}

  // {{{ SetProtocol() - sets query protocol to use
  // @param  string $arg1  protocol to use for gameserver query
  // @return bool          always TRUE
  // @access public
  function SetProtocol($string)
  {
    $this->_protocol = $string;
    trigger_error("<b>Set Protocol</b>: ".$string);
    return TRUE;
  }
  // }}}

  // {{{ SetIpPort() - sets ip, port and optional queryport
  // @param  string $arg1  format: "ip:port" or "ip:port:queryport"
  // @return bool          always TRUE
  // @access public
  function SetIpPort($string)
  {
    if (substr_count($string, ":") == 2)
      list($this->_ip, $this->_port, $this->_queryport) = explode(":", $string);
    else
    {
      list($this->_ip, $this->_port) = explode(":", $string);
      $this->_queryport = 0;
    }
    $this->_port = intval($this->_port);
    $this->_queryport = intval($this->_queryport);

    trigger_error("<b>Set Gameserver IP</b>: ".$this->_ip);
    trigger_error("<b>Set Gameserver Port</b>: ".$this->_port);
    if ($this->_queryport != 0)
      trigger_error("<b>Set Gameserver QueryPort</b>: ".$this->_queryport);
    return TRUE;
  }
  // }}}

  // {{{ SetRequestData() - sets data which query will request from gameserver
  // @param  array $arg1  data to request from gameserver
  // @return bool         always TRUE
  // @access public
  function SetRequestData($gettypes)
  {
    if (!is_array($gettypes))
    {
      $this->_SoftError("SetRequestData(): argument 1 is not an array");
      return FALSE;
    }

    $this->_gettypes = $gettypes;

    if ($this->_gettypes[0] == "FullInfo")
      $this->_fullinfo = 1;

    foreach ($this->_gettypes as $type)
      trigger_error("<b>Set RequestData</b>: ".$type);
    return TRUE;
  }
  // }}}

  // {{{ SetProtocols() - set autodetect protocols
  // @param  array $arg1  protocols to autodetect
  // @return bool         always TRUE
  // @access public
  function SetProtocols($protocols)
  {
    if (!is_array($protocols))
    {
      $this->_SoftError("SetProtocols(): argument 1 is not an array");
      return FALSE;
    }

    $this->_protocols = $protocols;
    foreach ($this->_protocols as $type)
      trigger_error("<b>Set AutoDetect Protocols</b>: ".$type);
    return TRUE;
  }
  // }}}

  // {{{ SetLocalQuery() - set local query (used for halflife query)
  // @param  int          0 or 1
  // @return bool         always TRUE
  // @access public
  function SetLocalQuery($enable)
  {
    $this->_localquery = $enable;
    trigger_error("<b>Set Local Query</b>: ".$this->_localquery);
    return TRUE;
  }
  // }}}

  // {{{ SetSocketTimeOut() - sets an optional socket timeout
  // @param  int $arg1  socket timeout in seconds
  //         int $arg2  socket timeout in microseconds
  // @return bool       always TRUE
  // @access public
  function SetSocketTimeOut($stimeout, $mstimeout)
  {
    $this->_stimeout  = $stimeout;
    $this->_mstimeout = $mstimeout;
    trigger_error("<b>Set Socket Timeout Seconds</b>: ".$this->_stimeout);
    trigger_error("<b>Set Socket Timeout MicroSeconds</b>: ".$this->_mstimeout);
    return TRUE;
  }
  // }}}

  // {{{ SetSocketLoopTimeOut() - sets an optional socket loop timeout
  // @param  int $arg1  socket loop timeout in microseconds
  // @return bool       always TRUE
  // @access public
  function SetSocketLoopTimeOut($timeout)
  {
    $this->_looptimeout = $timeout;
    trigger_error("<b>Set Socket Loop Timeout</b>: ".$this->_looptimeout);
    return TRUE;
  }
  // }}}

  // {{{ GetData() - requests data from gameserver and returns an array
  // @return array  requested data as array in format: $array["data1"][...]
  //         bool   FALSE if an error occur
  // @access public
  function GetData()
  {
    $recvdata = array();

    $this->_CheckSettings();
    if ($this->ERROR)
      return FALSE;
    if ($this->_fullinfo && $this->_protocol != "AutoDetect")
    {
      $recvdata = call_user_func(array(&$this, "_".$this->_protocol."FullInfo"));
      $recvdata["Protocol"] = $this->_protocol;
    }
    else
      $recvdata = call_user_func(array(&$this, "_".$this->_protocol."Main"));
    $this->_CleanUP();

    return $recvdata;
  }
  // }}}

  // {{{ _GetMicroTime() - returns current timestamp as microseconds
  // @return float  timestamp as micrososeconds
  // @access protected
  function _GetMicroTime()
  {
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec); 
  }
  // }}}

  // {{{ _CleanUp() - class clean up
  // @return bool  always TRUE
  // @access protected
  function _CleanUp()
  {
    restore_error_handler();
    return TRUE;
  }
  // }}}

  // {{{ _CheckSettings() - check for valid settings
  // @return bool  TRUE if no error occur
  //               FALSE otherwise
  // @access protected
  function _CheckSettings()
  {
    $type = "";

    // check available protocol
    if ($this->_protocol == "")
    {
      $this->_SoftError("No protocol set");
      return FALSE;
    }
    if (!is_callable(array(&$this, "_".$this->_protocol."Main")))
    {
      $this->_SoftError("\"".$this->_protocol."\" protocol not available");
      return FALSE;
    }

    // check available methodes for this protocol
    if (empty($this->_gettypes))
    {
      $this->_SoftError("No requesttypes set");
      return FALSE;
    }
    if ($this->_protocol != "AutoDetect")
    {
      foreach ($this->_gettypes as $type)
      {
        if ($type != "RCon")
        {
          if (!is_callable(array(&$this, "_".$this->_protocol.$type)))
          {
            $this->_SoftError("SetRequestData() Type: \"".$type."\" now known in Protocol: \"".$this->_protocol."\"");
            return FALSE;
          }
        }
      }
    }

    // check ip and port
    if ($this->_ip == "" || $this->_port == "")
    {
      $this->_SoftError("No IP or Port set");
      return FALSE;
    }
    if (!preg_match("/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})$/", gethostbyname($this->_ip)))
    {
      $this->_SoftError("Wrong Gameserver IP Format");
      return FALSE;
    }
    if (!is_integer($this->_port) || $this->_port <= 0 || 65536 < $this->_port)
    {
      $this->_SoftError("Wrong Gameserver Port Format");
      return FALSE;
    }
    if ($this->_queryport != 0 && (!is_integer($this->_queryport) || $this->_queryport <= 0 || 65536 < $this->_queryport))
    {
      $this->_SoftError("Wrong Gameserver QueryPort Format");
      return FALSE;
    }

    // check and calculate socket loop timeout
    if ($this->_looptimeout < 500)  $this->_looptimeout = 500;
    if ($this->_looptimeout > 2000) $this->_looptimeout = 2000;
    $this->_looptimeout = doubleval($this->_looptimeout / 1000.0);

    return TRUE;
  }
  // }}}

  // {{{ _ErrorHandler() - main php error handler
  // @param  -> reference to php documentation
  // @return bool  always TRUE
  //               stops script if error number is E_USER_ERROR
  // @access protected
  function _ErrorHandler($errno, $errstr, $errfile, $errline)
  {
    switch ($errno) {
      case E_USER_ERROR:
        echo "<b>[FATAL]</b> ".$errstr." in <b>".$errfile."</b> on line <b>".$errline."</b><br />\n";
        exit(1);
        break;
      case E_USER_WARNING:
        echo "<b>[ERROR]</b> ".$errstr." in <b>".$errfile."</b> on line <b>".$errline."</b><br />\n";
        break;
      case E_USER_NOTICE:
        if ($this->DEBUG)
          echo "<b>[DEBUG]</b> ".$errstr."</b>\n";
        break;
      case E_NOTICE:
        echo "<b>[NOTICE]</b> ".$errstr." in <b>".$errfile."</b> on line <b>".$errline."</b><br />\n";
        break;
      default:
        echo "<b>[UNKNOWN]</b> ".$errstr." in <b>".$errfile."</b> on line <b>".$errline."</b><br />\n";
        break;
    }
    return TRUE;
  }
  // }}}

  // {{{ _SoftError() - soft error handler
  // @param  string $arg1  error message
  // @return bool          always TRUE
  // @access proteced
  function _SoftError($errstr)
  {
    $this->ERROR  = ($errstr === false || strlen($errstr) === 0) ? FALSE : TRUE;
    $this->ERRSTR = $errstr;
    return TRUE;
  }
  // }}}

  // {{{ _CreateUDPSocket() - creates an udp socket
  // @return bool  always TRUE
  //               stops script if an error occur
  // @access protected
  function _CreateUDPSocket()
  {
    if ($this->_queryport == 0)
      $this->_queryport = $this->_port;
    if (!$this->_socket = fsockopen("udp://".$this->_ip, $this->_queryport, $errnr, $errstr))
    {
      trigger_error("Could not create socket (".$errstr.")", E_USER_ERROR);
    }
    socket_set_blocking($this->_socket, TRUE);
    socket_set_timeout($this->_socket, $this->_stimeout, $this->_mstimeout);
    return TRUE;
  }
  // }}}

  // {{{ _SendSocket() - write data to socket
  // @param  string $arg1  string to write to socket
  // @return bool          always TRUE
  //                       stops script if an error occur
  // @access protected
  function _SendSocket($string)
  {
    if (!fwrite($this->_socket, $string, strlen($string)))
      trigger_error("Could not send data", E_USER_ERROR);
    return TRUE;
  }
  // }}}

  // {{{ _GetSocketData() - read data from socket
  // @return string  data received from socket buffer
  //         bool    FALSE and triggers _SoftError() if an error occur 
  // @access protected
  function _GetSocketData()
  {
    $recv = "";
    $socketstatus = array();
    $start = $this->_GetMicroTime();
    do
    {
      $recv .= fgetc($this->_socket);
      $socketstatus = socket_get_status($this->_socket);
      if ($this->_GetMicroTime() > ($start + $this->_looptimeout))
      {
        $this->_CloseUDPSocket();
        $this->_SoftError("Connection to server timeout out");
        return FALSE;
      }
    }
    while ($socketstatus["unread_bytes"]);

    if ($recv == "")
      $this->_SoftError("Nothing received from server");

    return $recv;
  }
  // }}}

  // {{{ _GetSocketDataNr() - read multiple times from socket
  // @param  int $arg1  number how often _GetSocketData() will be called
  // @return array      one array element for every received data
  //                    triggers _SoftError() if no data will be received
  // @access protected
  function _GetSocketDataNr($nr)
  {
    $recv = "";
    $data = array();
    for ($i = 0; $i < $nr; $i++)
    {
      $recv = $this->_GetSocketData();
      if ($recv != "")
        array_push($data, $recv);
    }
    if (count($data))
      $this->_SoftError(FALSE);

    return $data;
  }
  // }}}

  // {{{ _CloseUDPSocket() - close an udp socket
  // @return bool  always TRUE
  //               stops script if socket cannot be closed
  // @access protected
  function _CloseUDPSocket()
  {
    if (!fclose($this->_socket))
      trigger_error("Could not close socket", E_USER_ERROR);
    return TRUE;
  }
  // }}}

  // {{{ _CheckQueryHeader() - checks for query header
  // @param  string $arg1  string to search in. string will be shortened automatically
  //         string $arg2  string to search for
  //         string $arg3  will be set to the snippet-string off $arg1
  // @return bool          TRUE if $arg2 was found
  //                       FALSE otherwise
  // @access protected
  function _CheckQueryHeader(&$data, $header, &$snippet)
  {
    $offset  = 0;
    $snippet = "";

    if ($this->ERROR)
      return FALSE;

    if (($offset = strpos($data, $header)) !== FALSE)
    {
      $snippet = substr($data, 0, $offset);
      $data = substr($data, $offset + strlen($header));
      return TRUE;
    }
    $this->_SoftError("No query header found in received data");
    return FALSE;
  }
  // }}}

  // {{{ _CheckQueryFooter() - checks for query footer
  // @param  string $arg1  string to search in. string will be shortened automatically
  //         string $arg2  string to search for
  //         string $arg3  will be set to the snippet-string off $arg1
  // @return bool          TRUE if $arg2 was found
  //                       FALSE otherwise
  // @access protected
  function _CheckQueryFooter(&$data, $footer, &$snippet)
  {
    $offset  = 0;
    $snippet = "";

    if ($this->ERROR)
      return FALSE;

    if (($offset = strpos($data, $footer)) !== FALSE)
    {
      $snippet = substr($data, $offset + strlen($footer));
      $data = substr($data, 0, $offset);
      return TRUE;
    }
    $this->_SoftError("No query footer found in received data");
    return FALSE;
  }
  // }}}

  // {{{ _GetCharacterTerminatedString() - get a character-terminated-string
  // @param  string $arg1  string to search in. string will be shortened automatically
  // @param  char   $arg2  character to search for
  // @return string        first character-terminated string
  // @access protected
  function _GetCharacterTerminatedString(&$data, $chr)
  {
    $str     = "";
    $counter = 0;
    while ((strlen($data) > $counter) && ($data{$counter++} != $chr))
      $str .= $data{$counter-1};
    $data = substr($data, strlen($str) + 1);
    return $str;
  }
  // }}}

  // {{{ _GetDelimitedVariables() - splits a string delimated by a character
  // @param  string $arg1  string to search in
  // @return array         splitted array. format: $data["before_character"] = after_character
  // @access protected
  function _GetDelimitedVariables(&$data, $delimiter)
  {
    $name  = "";
    $value = "";
    $vars  = array();

    $name = strtok($data, $delimiter);
    $value = strtok($delimiter);
    while (strlen($name))
    {
      $vars[$name] = $value;
      $name = strtok($delimiter);
      $value = strtok($delimiter);
    }
    return $vars;
  }
  // }}}

  // {{{ _GetByteAsChr() -  get one byte as character
  // @param  string $arg1  string to search in. string will be shortened automatically
  // @return string        first byte of string as ascii character
  //         bool          FALSE if length of $arg1 is zero
  // @access protected
  function _GetByteAsChr(&$data)
  {
    $str = "";
    if (!strlen($data))
      return FALSE;
    $str = $data{0};
    $data = substr($data, 1);
    return $str;
  }
  // }}}

  // {{{ _GetByteAsAscii() - get one byte as ascii value
  // @param  string $arg1  string to search in. string will be shortened automatically
  // @return string        first byte of string as type ascii value
  // @access protected
  function _GetByteAsAscii(&$data)
  {
    $str = "";
    $str = ord($this->_GetByteAsChr($data));
    return $str;
  }
  // }}}

  // {{{ _GetStringByLength() - get a string by length
  // @param  string $arg1  string to snip. string will be shortened automatically
  //         int    $arg2  length to snip off
  // @return string        snippet string
  // @access protected
  function _GetStringByLength(&$data, $length)
  {
    $str  = "";
    $str  = substr($data, 0, $length);
    $data = substr($data, strlen($str));
    return $str;
  }
  // }}}

  // {{{ _GetInt16AsInt() - get int16 value
  // @param  string $arg1  string to search in. string will be shortened automatically
  // @return int           corresponding int16 value
  //         bool          FALSE if length of $arg1 is too short
  // @access proteced
  function _GetInt16AsInt(&$data)
  {
    $str = "";
    if (strlen($data) < 2)
      return FALSE;
    $str = $this->_GetByteAsChr($data).$this->_GetByteAsChr($data);
    $str = unpack('sint', $str);
    return $str["int"];
  }
  // }}}

  // {{{ _GetInt32AsInt() - get int32 value
  // @param  string $arg1  string to search in. string will be shortened automatically
  // @return int           corresponding int32 value
  //         bool          FALSE if length of $arg1 is too short
  // @access proteced
  function _GetInt32AsInt(&$data)
  {
    $str = "";
    if (strlen($data) < 4)
      return FALSE;
    $str = $this->_GetByteAsChr($data).$this->_GetByteAsChr($data).$this->_GetByteAsChr($data).$this->_GetByteAsChr($data);
    $str = unpack('iint', $str);
    return $str["int"];
  }
  // }}}

  // {{{ _GetFloat32AsFloat() - get float32 value
  // @param  string $arg1  string to search in. string will be shortened automatically
  // @return float         corresponding float32 value
  //         bool          FALSE if length of $arg1 is too short
  // @access proteced
  function _GetFloat32AsFloat(&$data)
  {
    $str = "";
    if (strlen($data) < 4)
      return FALSE;
    $str = $this->_GetByteAsChr($data).$this->_GetByteAsChr($data).$this->_GetByteAsChr($data).$this->_GetByteAsChr($data);
    $str = unpack('fint', $str);
    return $str["int"];
  }
  // }}}

  // {{{ _GetLongAsLong() - get long value
  // @param  string $arg1  string to search in. string will be shortened automatically
  // @return long          corresponding long value
  //         bool          FALSE if length of $arg1 is too short
  // @access proteced
  function _GetLongAsLong(&$data)
  {
    $str = "";
    if (strlen($data) < 4)
      return FALSE;
    $str = $this->_GetByteAsChr($data).$this->_GetByteAsChr($data).$this->_GetByteAsChr($data).$this->_GetByteAsChr($data);
    $str = unpack('llong', $str);
    return $str["long"];
  }
  // }}}


  // {{{ _HexDump() - dump bytes as hex
  // @param  string $arg1  data to dump
  // @return bool          TRUE
  // @access proteced
  function _HexDump($data)
  {
    echo  "Length: ".strlen($data)."\n";

    $cache = "";
    for ($i = 0; $i < strlen($data); $i++)
    {
      if ($i % 16 == 0)
        printf("%08x  ", $i);
      elseif ($i % 8 == 0)
        echo "  ";
      else
        echo " ";

      $cache .= $data{$i};
      printf("%02x", ord($data{$i}));

      if (strlen($cache) == 16 || $i == strlen($data) - 1)
      {
        if (strlen($cache) < 16)
        {
          $shift = "";
          for ($j = strlen($cache); $j < 16; $j++)
          {
            if ($j % 8 == 0)
              $shift .= "  ";
            $shift .= "   ";
          }
          echo substr($shift, 0, strlen($shift) - 1);
          if (strlen($shift) < 3*8)
            echo " ";
        }

        echo "  |";
        for ($j = 0; $j < strlen($cache); $j++)
        {
          $chr = $cache{$j};
          if (ord($chr) < ord("\x20") || ord($chr) > ord("\x7E"))
            $chr = ".";
          echo htmlentities($chr);
        }
        echo "|";
        echo "\n";

        $cache = "";
      }
    }

    return TRUE;
  }
  // }}}


  // {{{ _AutoDetectMain() - main methode of the autodetect routine
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _AutoDetectMain()
  {
    $protocol = "";
    $subproto = "";
    $data     = array();

    // first some checks
    if (empty($this->_protocols))
    {
      $this->_SoftError("No autodetect protocols set");
      return FALSE;
    }
    foreach ($this->_protocols as $protocol)
    {
      if (!is_callable(array(&$this, "_".$protocol."Main")))
      {
        $this->_SoftError("\"".$protocol."\" protocol not available");
        return FALSE;
      }
      if (!is_callable(array(&$this, "_".$protocol."AutoDetect")))
      {
        $this->_SoftError("No Autodetection method available for Protocol: \"".$protocol."\"");
        return FALSE;
      }
      foreach ($this->_gettypes as $subproto)
      {
        if (!is_callable(array(&$this, "_".$protocol.$subproto)))
        {
          $this->_SoftError("SetRequestData() Type: \"".$subproto."\" now known in Protocol: \"".$protocol."\"");
          return FALSE;
        }
      }
    }
    if ($this->_queryport != 0)
    {
      $this->_SoftError("Never set a queryport if you use autodetection");
      return FALSE;
    }

    trigger_error("<b>Starting AutoDetection</b>");
    foreach ($this->_protocols as $type)
    {
      call_user_func(array(&$this, "_".$type."AutoDetect"));
      $autodetect = new GSQuery();
      $autodetect->SetProtocol($type);
      $autodetect->SetIpPort($this->_ip.":".$this->_port.":".$this->_queryport);
      $autodetect->SetRequestData($this->_gettypes);
      $autodetect->SetSocketTimeOut($this->_stimeout, $this->_mstimeout);
      $autodetect->SetSocketLoopTimeOut($this->_looptimeout);
      $data = $autodetect->GetData();
      $this->ERROR  = $autodetect->ERROR;
      $this->ERRSTR = $autodetect->ERRSTR;
      if ($autodetect->ERROR == FALSE)
        break;
    }

    return $data;
  }
  // }}}

  // {{{ Query Protocol definitions
  // {{{ Query Protocol: HalfLife
  // {{{ _HalfLifeMain() - Query Protocol: HalfLife - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _HalfLifeMain()
  {
    $challenge = "\xFF\xFF\xFF\xFF";
    $data = array();
    $this->_CreateUDPSocket();

    if (!$this->_localquery)
      $challenge = call_user_func(array(&$this, "_".$this->_protocol."Challenge"));

    foreach ($this->_gettypes as $type)
    {
      $data[$type] = call_user_func(array(&$this, "_".$this->_protocol.$type), $challenge);
      if ($this->ERROR)
        return FALSE;
     }
    $this->_CloseUDPSocket();

    return $data;
  }
  /// }}}

  // {{{ Query Protocol definitions
  // {{{ Query Protocol: HalfLife
  // {{{ _HalfLifeRecv() - Query Protocol: HalfLife - Type: Recv
  // @access protected
  function _HalfLifeRecv($query)
  {
    $this->_SendSocket($query);

    $recv       = "";
    $packets    = array();
    $packetcnt  = 1;
    $compressed = false;
    for ($i = 0; $i < $packetcnt; $i++)
    {
      $recv = implode("", $this->_GetSocketDataNr(1));
      # split packet
      if (substr($recv, 0, 4) == "\xFE\xFF\xFF\xFF")
      {
        $recv = substr($recv, 4);
        $requestid = $this->_GetLongAsLong($recv); # maybe check too?
        $compressed = (($requestid & (int)(1 << 31)) == (int)(1 << 31)) ? true : false;
        if (isset($this->_globalvars["ProtocolVersion"]) && $this->_globalvars["ProtocolVersion"] == 48)
        {
          $tmp = $this->_GetByteAsAscii($recv);
          $packetcnt = $tmp & 0xF;
          $packetnum = ($tmp >> 4) & 0xF;
        }
        else
        {
          $packetcnt = $this->_GetByteAsAscii($recv);
          $packetnum = $this->_GetByteAsAscii($recv);
        }
        # only in tf2 and newer source engines. should be harcoded?!
        if (substr($recv, 0, 2) == "\xE0\x04")
          $this->_GetInt16AsInt($recv);
        if ($compressed)
        {
          $realsize = $this->_GetInt32AsInt($recv);
          $realcrc32 = $this->_GetInt32AsInt($recv);
        }
        $packets[$packetnum] = $recv;
      }
      # single packet
      elseif (substr($recv, 0, 4) == "\xFF\xFF\xFF\xFF")
      {
        $packets[0] = $recv;
      }
      # unknown
      else
      {
        $this->_SoftError("Unknown header in half-life packet");
        return FALSE;
      }
    }

    $recv = implode("", $packets);
    if ($compressed)
      $recv = bzdecompress($recv);

    return $recv;
  }
  /// }}}


  // {{{ _HalfLifeDetails() - Query Protocol: HalfLife - Type: Server Details
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _HalfLifeDetails()
  {
    $recv   = "";
    $prefix = "";
    $data   = array();
    $tmp    = array();

    $this->_SendSocket("\xFF\xFF\xFF\xFFTSource Engine Query\x00");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFF\xFF\xFF", $prefix))
      return FALSE;

    # Half-Life reply
    if ($recv{0} == "m")
    {
      $recv = substr($recv, 1);
      $tmp = explode(":", $this->_GetCharacterTerminatedString($recv, "\x00"));
      $data["Ip"]              = $tmp[0];
      $data["Port"]            = $tmp[1];
      $data["Hostname"]        = $this->_GetCharacterTerminatedString($recv, "\x00");
      $data["Map"]             = $this->_GetCharacterTerminatedString($recv, "\x00");
      $data["GameDir"]         = $this->_GetCharacterTerminatedString($recv, "\x00");
      $data["GameDesc"]        = $this->_GetCharacterTerminatedString($recv, "\x00");
      $data["PlayerCount"]     = $this->_GetByteAsAscii($recv);
      $data["MaxPlayers"]      = $this->_GetByteAsAscii($recv);
      $data["ProtocolVersion"] = $this->_GetByteAsAscii($recv);
      $data["ServerType"]      = $this->_GetByteAsChr($recv);
      $data["ServerOS"]        = $this->_GetByteAsChr($recv);
      $data["Password"]        = $this->_GetByteAsAscii($recv);
      $data["Modded"]          = $this->_GetByteAsAscii($recv);
      if ($data["Modded"])
      {
        $data["ModWebsite"]        = $this->_GetCharacterTerminatedString($recv, "\x00");
        $data["ModDownloadServer"] = $this->_GetCharacterTerminatedString($recv, "\x00");
        $this->_GetCharacterTerminatedString($recv, "\x00");
        $data["ModVersion"]        = $this->_GetInt32AsInt($recv);
        $data["ModSize"]           = $this->_GetInt32AsInt($recv);
        $data["ModServerSideOnly"] = $this->_GetByteAsAscii($recv);
        $data["ModCustomDLL"]      = $this->_GetByteAsAscii($recv);
      }
      $data["Secure"] = $this->_GetByteAsAscii($recv);
    }
    # Half-Life Source reply
    elseif ($recv{0} == "I")
    {
      $recv = substr($recv, 1);
      $data["ProtocolVersion"] = $this->_GetByteAsAscii($recv);
      $this->_globalvars["ProtocolVersion"] = $data["ProtocolVersion"];
      $data["Hostname"]        = $this->_GetCharacterTerminatedString($recv, "\x00");
      $data["Map"]             = $this->_GetCharacterTerminatedString($recv, "\x00");
      $data["GameDir"]         = $this->_GetCharacterTerminatedString($recv, "\x00");
      $data["GameDesc"]        = $this->_GetCharacterTerminatedString($recv, "\x00");
      $data["SteamAppID"]      = $this->_GetInt16AsInt($recv);
      $data["PlayerCount"]     = $this->_GetByteAsAscii($recv);
      $data["MaxPlayers"]      = $this->_GetByteAsAscii($recv);
      $data["BotCount"]        = $this->_GetByteAsAscii($recv);
      $data["ServerType"]      = $this->_GetByteAsChr($recv);
      $data["ServerOS"]        = $this->_GetByteAsChr($recv);
      $data["Password"]        = $this->_GetByteAsAscii($recv);
      $data["Secure"]          = $this->_GetByteAsAscii($recv);
    }
    else
      $this->_SoftError("Unknown reply from HalfLife");

    return $data;
  }
  // }}}

  // {{{ _HalfLifeChallenge() - Query Protocol: HalfLife - get challenge string for quering
  // @return string  challenge string
  // @access protected
  function _HalfLifeChallenge()
  {
    $recv   = "";
    $prefix = "";
    $challenge = "";

    // there's a bug in new hlds protocol which fucks up the challenge query.
    // so fallback to A2S_PLAYER with -1 as challenge id */
    //$this->_SendSocket("\xFF\xFF\xFF\xFFW");
    $this->_SendSocket("\xFF\xFF\xFF\xFFV\xFF\xFF\xFF\xFF");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFF\xFF\xFFA", $prefix))
      return FALSE;

    $challenge = substr($recv, 0, 4);

    return $challenge;
  }
  // }}}

  // {{{ _HalfLifeRules() - Query Protocol: HalfLife - Type: Server Rules
  // @param  string challenge string
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _HalfLifeRules($challenge)
  {
    $prefix = "";
    $recv = $this->_HalfLifeRecv("\xFF\xFF\xFF\xFFV".$challenge);

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFF\xFF\xFFE", $prefix))
      return FALSE;

    $data["RuleCount"] = $this->_GetInt16AsInt($recv);
    for ($i = 0; $i < $data["RuleCount"]; $i++)
      $data[$this->_GetCharacterTerminatedString($recv, "\x00")] = $this->_GetCharacterTerminatedString($recv, "\x00");

    return $data;
  }
  // }}}

  // {{{ _HalfLifePlayers() - Query Protocol: HalfLife - Type: Players
  // @param  string challenge string
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _HalfLifePlayers($challenge)
  {
    $prefix = "";
    $recv = $this->_HalfLifeRecv("\xFF\xFF\xFF\xFFU".$challenge);

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFF\xFF\xFFD", $prefix))
      return FALSE;

    $data["PlayerCount"] = $this->_GetByteAsAscii($recv);
    $data["Players"] = array();
    for ($i = 0; $i < $data["PlayerCount"]; $i++)
    {
      $player = array();
      $player["Number"] = $this->_GetByteAsAscii($recv);
      $player["Name"]   = $this->_GetCharacterTerminatedString($recv, "\x00");
      $player["Score"]  = $this->_GetInt32AsInt($recv);
      $player["Time"]   = round($this->_GetFloat32AsFloat($recv), 0) + 82800;
      if ($player["Name"] == "" && $player["Score"] == 0 && $player["Time"] == 82800)
        $player["Number"] = $player["Time"] = 0;
      array_push($data["Players"], $player);
    }

    return $data;
  }
  // }}}

  // {{{ _HalfLifeAutoDetect() - Query Protocol: HalfLife - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _HalfLifeAutoDetect()
  {
    $this->_queryport = $this->_port;
    return TRUE;
  }
  // }}}

  // {{{ _HalfLifeFullInfo() - Query Protocol: HalfLife - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  // @access protected
  function _HalfLifeFullInfo()
  {
    $this->SetRequestData(array("Details", "Rules", "Players"));
    return $this->_HalfLifeMain();
  }
  // }}}
  // }}}

  // {{{ Query Protocol: AllSeeingEye
  // {{{ _AllSeeingEyeMain() - Query Protocol: AllSeeingEye - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _AllSeeingEyeMain()
  {
    $recv   = "";
    $prefix = "";
    $tmp    = array();
    $data   = array();

    $this->_CreateUDPSocket();
    $this->_SendSocket("s");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryHeader($recv, "EYE1", $prefix))
      return FALSE;

    $tmp["Details"] = $this->_AllSeeingEyeDetails($recv);
    $tmp["Players"] = $this->_AllSeeingEyePlayers($recv);
    if ($this->ERROR)
      return FALSE;
    $this->_CloseUDPSocket();


    foreach ($this->_gettypes as $type)
      $data[$type] = $tmp[$type];

    return $data;
  }
  // }}}

  // {{{ _AllSeeingEyeDetails() - Query Protocol: AllSeeingEye - Type: Server Details
  // @return array  decoded data received from gameserver
  // @access protected
  function _AllSeeingEyeDetails(&$recv)
  {
    $tmp  = "";
    $data = array();

    $data["GameName"]    = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
    $data["Port"]        = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
    $data["HostName"]    = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
    $data["GameTyp"]     = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
    $data["Map"]         = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
    $data["Version"]     = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
    $data["Password"]    = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
    $data["PlayerCount"] = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
    $data["MaxPlayers"]  = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);

    while (($tmp = $this->_GetByteAsAscii($recv)) != "1")
      $data[$this->_GetStringByLength($recv, $tmp - 1)] = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);

    return $data;
  }
  // }}}

  // {{{ _AllSeeingEyePlayers() - Query Protocol: AllSeeingEye - Type: Players
  // @return array  decoded data received from gameserver
  // @access protected
  function _AllSeeingEyePlayers(&$recv)
  {
    $tmp     = "";
    $counter = 0;
    $players = array();

    if (strlen($recv) == 0)
      return $players;

    while(strlen($recv) > 0)
    {
      $tmp = $this->_GetByteAsAscii($recv);
      if ($tmp & 1)
        $players[$counter]["Name"]  = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
      if ($tmp & 2)
        $players[$counter]["Team"]  = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
      if ($tmp & 4)
        $players[$counter]["Skin"]  = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
      if ($tmp & 8)
        $players[$counter]["Score"] = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
      if ($tmp & 16)
        $players[$counter]["Ping"]  = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
      if ($tmp & 32)
        $players[$counter]["Time"]  = $this->_GetStringByLength($recv, $this->_GetByteAsAscii($recv) - 1);
      $counter++;
    }

    return $players;
  }
  // }}}

  // {{{ _AllSeeingEyeAutoDetect() - Query Protocol: AllSeeingEye - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _AllSeeingEyeAutoDetect()
  {
    $this->_queryport = $this->_port + 123;
    return TRUE;
  }
  // }}}

  // {{{ _AllSeeingEyeFullInfo() - Query Protocol: AllSeeingEye - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  // @access protected
  function _AllSeeingEyeFullInfo()
  {
    $this->SetRequestData(array("Details", "Players"));
    return $this->_AllSeeingEyeMain();
  }
  // }}}
  // }}}

  // {{{ Query Protocol: Quake1
  // {{{ _Quake1Main() - Query Protocol: Quake1 - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _Quake1Main()
  {
    $recv   = "";
    $prefix = "";
    $tmp    = array();
    $data   = array();

    $this->_CreateUDPSocket();
    $this->_SendSocket("\xFF\xFF\xFF\xFFstatus");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFF\xFF\xFFn\\", $prefix))
      return FALSE;

    $tmp["Details"] = substr($recv, 0, strpos($recv, "\n"))."\\";
    $tmp["Players"] = substr($recv, strpos($recv,"\n") + 1);
    $tmp["Players"] = substr($tmp["Players"], 0, strlen($tmp["Players"]) - 1);

    foreach ($this->_gettypes as $type)
    {
      $data[$type] = call_user_func(array(&$this, "_".$this->_protocol.$type), $tmp[$type]);
      if ($this->ERROR)
        return FALSE;
    }
    $this->_CloseUDPSocket();

    return $data;
  }
  // }}}

  // {{{ _Quake1Details() - Query Protocol: Quake1 - Type: Server Details
  // @return array  decoded data received from gameserver
  // @access protected
  function _Quake1Details($recv)
  {
    $data = array();
    $data = $this->_GetDelimitedVariables($recv, "\\");
    return $data;
  }
  // }}}

  // {{{ _Quake1Players() - Query Protocol: Quake1 - Type: Players
  // @return array  decoded data received from gameserver
  // @access protected
  function _Quake1Players($recv)
  {
    $counter = 0;
    $data    = array();
    $player  = array();
    $players = array();

    if (strlen($recv) == 0)
      return $players;

    $data = explode("\n", $recv);
    foreach ($data as $line)
    {
      if (strlen($line) == 0)
        continue;
      if (preg_match("/^([-0-9]+) ([-0-9]+) ([-0-9]+) ([-0-9]+) \"(.*)\" \"(.*)\" ([-0-9]+) ([-0-9]+)$/i", $line, $player))
      {
        $players[$counter]["Number"] = $player[1];
        $players[$counter]["Score"]  = $player[2];
        $players[$counter]["Time"]   = $player[3];
        $players[$counter]["Ping"]   = $player[4];
        $players[$counter]["Name"]   = $player[5];
        $players[$counter]["Skin"]   = $player[6];
        $players[$counter]["Score1"] = $player[7];
        $players[$counter]["Score2"] = $player[8];
        $counter++;
      }
      else
        $this->_SoftError("Unknown players data format received in Protocol: \"".$this->_protocol."\"");
    }
    return $players;
  }
  // }}}

  // {{{ _Quake1AutoDetect() - Query Protocol: Quake1 - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _Quake1AutoDetect()
  {
    $this->_queryport = $this->_port;
    return TRUE;
  }
  // }}}

  // {{{ _Quake1FullInfo() - Query Protocol: Quake1 - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  function _Quake1FullInfo()
  {
    $this->SetRequestData(array("Details", "Players"));
    return $this->_Quake1Main();
  }
  // }}}
  // }}}

  // {{{ Query Protocol: Quake2
  // {{{ _Quake2Main() - Query Protocol: Quake2 - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _Quake2Main()
  {
    $recv   = "";
    $prefix = "";
    $tmp    = array();
    $data   = array();

    $this->_CreateUDPSocket();
    $this->_SendSocket("\xFF\xFF\xFF\xFFstatus");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFF\xFF\xFFprint\x0A", $prefix))
      return FALSE;

    $tmp["Details"] = substr($recv, 0, strpos($recv, "\n"))."\\";
    $tmp["Players"] = substr($recv, strpos($recv,"\n") + 1);
    $tmp["Players"] = substr($tmp["Players"], 0, strlen($tmp["Players"]) - 1);

    foreach ($this->_gettypes as $type)
    {
      $data[$type] = call_user_func(array(&$this, "_".$this->_protocol.$type), $tmp[$type]);
      if ($this->ERROR)
        return FALSE;
    }
    $this->_CloseUDPSocket();

    return $data;
  }
  // }}}

  // {{{ _Quake2Details() - Query Protocol: Quake2 - Type: Server Details
  // @return array  decoded data received from gameserver
  // @access protected
  function _Quake2Details($recv)
  {
    $data = array();
    $data = $this->_GetDelimitedVariables($recv, "\\");
    return $data;
  }
  // }}}

  // {{{ _Quake2Players() - Query Protocol: Quake2 - Type: Players
  // @return array  decoded data received from gameserver
  // @access protected
  function _Quake2Players($recv)
  {
    $counter = 0;
    $data    = array();
    $player  = array();
    $players = array();

    if (strlen($recv) == 0)
      return $players;

    $data = explode("\n", $recv);
    foreach ($data as $line)
    {
      if (strlen($line) == 0)
        continue;
      if (preg_match("/^([-0-9]+) ([-0-9]+) \"(.*)\"$/i", $line, $player))
      {
        $players[$counter]["Score"]  = $player[1];
        $players[$counter]["Ping"]   = $player[2];
        $players[$counter]["Name"]   = $player[3];
        $counter++;
      }
      else
        $this->_SoftError("Unknown players data format received in Protocol: \"".$this->_protocol."\"");
    }
    return $players;
  }
  // }}}

  // {{{ _Quake2AutoDetect() - Query Protocol: Quake2 - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _Quake2AutoDetect()
  {
    $this->_queryport = $this->_port;
    return TRUE;
  }
  // }}}

  // {{{ _Quake2FullInfo() - Query Protocol: Quake2 - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  function _Quake2FullInfo()
  {
    $this->SetRequestData(array("Details", "Players"));
    return $this->_Quake2Main();
  }
  // }}}
  // }}}

  // {{{ Query Protocol: Quake3
  // {{{ _Quake3Main() - Query Protocol: Quake3 - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _Quake3Main()
  {
    $recv   = "";
    $prefix = "";
    $tmp    = array();
    $data   = array();

    $this->_CreateUDPSocket();
    $this->_SendSocket("\xFF\xFF\xFF\xFFgetstatus\x0A");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFF\xFF\xFFstatusResponse\x0A\\", $prefix))
      return FALSE;

    $tmp["Details"] = substr($recv, 0, strpos($recv, "\n"))."\\";
    $tmp["Players"] = substr($recv, strpos($recv,"\n") + 1);
    $tmp["Players"] = substr($tmp["Players"], 0, strlen($tmp["Players"]) - 1);

    foreach ($this->_gettypes as $type)
    {
      $data[$type] = call_user_func(array(&$this, "_".$this->_protocol.$type), $tmp[$type]);
      if ($this->ERROR)
        return FALSE;
    }
    $this->_CloseUDPSocket();

    return $data;
  }
  // }}}

  // {{{ _Quake3Details() - Query Protocol: Quake3 - Type: Server Details
  // @return array  decoded data received from gameserver
  // @access protected
  function _Quake3Details($recv)
  {
    $data = array();
    $data = $this->_GetDelimitedVariables($recv, "\\");
    return $data;
  }
  // }}}

  // {{{ _Quake3Players() - Query Protocol: Quake3 - Type: Players
  // @return array  decoded data received from gameserver
  // @access protected
  function _Quake3Players($recv)
  {
    $data    = array();
    $player  = array();
    $players = array();

    if (strlen($recv) == 0)
      return $players;

    $data = explode("\n", $recv);
    foreach ($data as $line)
    {
      if (strlen($line) == 0)
        continue;

      if (preg_match("/^([-0-9]+) ([-0-9]+) \"(.*)\"$/i", $line, $player))
        array_push($players, array("frags" => $player[1], "ping" => $player[2], "name" => $player[3]));
      elseif (preg_match("/^([-0-9]+) ([-0-9]+) ([-0-9]+) \"(.*)\"$/i", $line, $player))
        array_push($players, array("frags" => $player[1], "ping" => $player[2], "deaths" => $player[3], "name" => $player[4]));
      else
        $this->_SoftError("Unknown players data format received in Protocol: \"".$this->_protocol."\"");
    }
    return $players;
  }
  // }}}

  // {{{ _Quake3AutoDetect() - Query Protocol: Quake3 - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _Quake3AutoDetect()
  {
    $this->_queryport = $this->_port;
    return TRUE;
  }
  // }}}

  // {{{ _Quake3FullInfo() - Query Protocol: Quake3 - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  // @access protected
  function _Quake3FullInfo()
  {
    $this->SetRequestData(array("Details", "Players"));
    return $this->_Quake3Main();
  }
  // }}}
  // }}}

  // {{{ Query Protocol: Doom3
  // {{{ _Doom3Main() - Query Protocol: Doom3 - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _Doom3Main($protocol = 1)
  {
    $recv   = "";
    $prefix = "";
    $tmp    = array();
    $data   = array();

    $this->_CreateUDPSocket();
    $this->_SendSocket("\xFF\xFFgetInfo\x00\x00\x00\x00\x00");
    $recv = implode("", $this->_GetSocketDataNr(5));

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFFinfoResponse\x00", $prefix))
      return FALSE;

    /* dirty hack to determine protocol version */
    if ($tmp2 = strstr($recv, "si_version\x00"))
    {
      /* strip off si_version */
      $this->_GetCharacterTerminatedString($tmp2, "\x00");
      $version = $this->_GetCharacterTerminatedString($tmp2, "\x00");
      if (strpos(strtolower($version), "doom") !== false)
        $protocol = 1;
      elseif (strpos(strtolower($version), "pray") !== false)
        $protocol = 2;
      elseif (strpos(strtolower($version), "quake4") !== false)
        $protocol = 3;
      elseif (strpos(strtolower($version), "etqw") !== false)
        $protocol = 4;
    }

    if ($protocol == 4) /* ETQW */
      $taskid = $this->_GetLongAsLong($recv);
    $challenge = $this->_GetLongAsLong($recv);
    $this->_protoversion = $this->_GetLongAsLong($recv);
    $this->_protoversion = sprintf("%u.%u", $this->_protoversion >> 16, $this->_protoversion & 0xFFFF);
    if ($protocol == 4) /* ETQW */
      $this->_infosize = $this->_GetLongAsLong($recv);

    $tmp["Details"] = $this->_Doom3Details($recv, $protocol);
    if (!isset($tmp["Details"]["queryprotocol"]))
      $tmp["Details"]["queryprotocol"] = $this->_protoversion;

    $tmp["Players"] = $this->_Doom3Players($recv, $protocol);

    $tmp["Details"]["osmask"] = $this->_GetLongAsLong($recv);
    if ($protocol == 4) /* ETQW */
    {
      $tmp["Details"]["ranked"]     = $this->_GetByteAsAscii($recv);
      $tmp["Details"]["timeleft"]   = $this->_GetInt32AsInt($recv);
      $tmp["Details"]["gamestate"]  = $this->_GetByteAsAscii($recv);
      $tmp["Details"]["servertype"] = $this->_GetByteAsAscii($recv);
      if ($tmp["Details"]["servertype"] == 0) /* regular server */
        $tmp["Details"]["interested_clients"] = $this->_GetByteAsAscii($recv);
      elseif ($tmp["Details"]["servertype"] == 0) /* tv server */
      {
        $tmp["Details"]["tv_numClients"] = $this->_GetByteAsAscii($recv);
        $tmp["Details"]["tv_maxClients"] = $this->_GetByteAsAscii($recv);
      }
    }
    if ($this->ERROR)
      return FALSE;
    $this->_CloseUDPSocket();

    foreach ($this->_gettypes as $type)
      $data[$type] = $tmp[$type];

    return $data;
  }
  // }}}

  // {{{ _Doom3Details() - Query Protocol: Doom3 - Type: Server Details
  // @return array  decoded data received from gameserver
  // @access protected
  function _Doom3Details(&$recv, $protocol = 1)
  {
    $key  = "";
    $data = array();

    while (($key = $this->_GetCharacterTerminatedString($recv, "\x00")) !== FALSE)
    {
      $value = $this->_GetCharacterTerminatedString($recv, "\x00");
      if ($key == "" && $value == "")
          break;
      $data[$key] = $value;
    }

    if ($protocol == 4) /* ETQW */
    {
      if (isset($data["si_map"]) && strpos($data["si_map"], ".entities") !== FALSE)
        $data["si_map"] = substr($data["si_map"], 0, -9);
    }

    return $data;
  }
  // }}}

  // {{{ _Doom3Players() - Query Protocol: Doom3 - Type: Players
  // @return array  decoded data received from gameserver
  // @access protected
  function _Doom3Players(&$recv, $protocol = 1)
  {
    $tmp     = "";
    $players = array();

    if (strlen($recv) == 0)
      return $players;

    while (($number = $this->_GetByteAsAscii($recv)) != 32)
    {
      $players[$number]["Ping"] = $this->_GetByteAsAscii($recv) + $this->_GetByteAsAscii($recv) * 256;
      if ($protocol != 4) /* ETQW */
        $players[$number]["Rate"] = $this->_GetLongAsLong($recv);
      $players[$number]["Name"] = $this->_GetCharacterTerminatedString($recv, "\x00");
      if ($protocol == 4) /* ETQW */
      {
        $players[$number]["ClanTagPos"] = ($this->_GetByteAsAscii($recv) == 1) ? 1 : 0;
        $players[$number]["ClanTag"] = $this->_GetCharacterTerminatedString($recv, "\x00");
      }

      if ($protocol == 3) /* Quake4 */
        $players[$number]["Clantag"] = $this->_GetCharacterTerminatedString($recv, "\x00");
      elseif ($protocol == 4) /* ETQW */
        $players[$number]["Bot"] = ($this->_GetByteAsAscii($recv) == 1) ? 1 : 0;
    }

    return $players;
  }
  // }}}

  // {{{ _Doom3AutoDetect() - Query Protocol: Doom3 - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _Doom3AutoDetect()
  {
    $this->_queryport = $this->_port;
    return TRUE;
  }
  // }}}

  // {{{ _Doom3FullInfo() - Query Protocol: Doom3 - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  // @access protected
  function _Doom3FullInfo($protocol = 1)
  {
    $this->SetRequestData(array("Details", "Players"));
    return $this->_Doom3Main($protocol);
  }
  // }}}
  // }}}

  // {{{ Query Protocol: GameSpy
  // {{{ _GameSpyMain() - Query Protocol: GameSpy - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpyMain()
  {
    $data = array();
    $this->_CreateUDPSocket();
    foreach ($this->_gettypes as $type)
    {
      $data[$type] = call_user_func(array(&$this, "_".$this->_protocol.$type));
      if ($this->ERROR)
        return FALSE;
    }
    $this->_CloseUDPSocket();

    return $data;
  }
  // }}}

  // {{{ _GameSpyDetails() - Query Protocol: GameSpy - Type: Server Details
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpyDetails()
  {
    $recv   = "";
    $suffix = "";
    $data   = array();

    $this->_SendSocket("\\info\\");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryFooter($recv, "final\\", $suffix))
      return FALSE;

    $data = $this->_GetDelimitedVariables($recv, "\\");
    return $data;
  }
  // }}}

  // {{{ _GameSpyRules() - Query Protocol: GameSpy - Type: Server Rules
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpyRules()
  {
    $recv   = "";
    $suffix = "";
    $data   = array();

    $this->_SendSocket("\\rules\\");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryFooter($recv, "final\\", $suffix))
      return FALSE;

    $data = $this->_GetDelimitedVariables($recv, "\\");
    return $data;
  }
  // }}}

  // {{{ _GameSpyPlayers() - Query Protocol: GameSpy - Type: Players
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpyPlayers()
  {
    $recv    = "";
    $suffix  = "";
    $type    = "";
    $tmp     = "";
    $counter = 0;
    $key     = 0;
    $data    = array();

    $this->_SendSocket("\\players\\");
    $recv = implode("", $this->_GetSocketDataNr(1));
    while ($counter < 4)
    {
      if (!$this->_CheckQueryFooter($recv, "final\\", $suffix))
        $recv .= implode("", $this->_GetSocketDataNr(1));
      else
      {
        $recv .= "final\\";
        break;
      }
      $counter++;
    }
    $recv = substr($recv, 1);

    if (!$this->_CheckQueryFooter($recv, "final\\", $suffix))
      return FALSE;

    while(strlen($recv) > 0)
    {
      $tmp = $this->_GetCharacterTerminatedString($recv, "\\");
      if (($counter = strrpos($tmp, "_")) !== FALSE)
      {
        $type = substr($tmp, 0, $counter);
        $key  = intval(substr($tmp, $counter+1));
        $data[$key][$type] = $this->_GetCharacterTerminatedString($recv, "\\");
      }
    }

    return $data;
  }
  // }}}

  // {{{ _GameSpyAutoDetect() - Query Protocol: GameSpy - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _GameSpyAutoDetect($arg = 1)
  {
    $this->_queryport = $this->_port + 1;
    return TRUE;
  }
  // }}}

  // {{{ _GameSpyFullInfo() - Query Protocol: GameSpy - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  // @access protected
  function _GameSpyFullInfo()
  {
    $this->SetRequestData(array("Details", "Rules", "Players"));
    return $this->_GameSpyMain();
  }
  // }}}

  // {{{ _GameSpyPortPlus10Main() - Query Protocol: GameSpy Port+10 - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpyPortPlus10Main()
  {
    return $this->_GameSpyMain();
  }
  // }}}

  // {{{ _GameSpyPortPlus10Details() - Query Protocol: GameSpy Port+10 - Type: Server Details
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpyPortPlus10Details()
  {
    return $this->_GameSpyDetails();
  }
  // }}}

  // {{{ _GameSpyPortPlus10Rules() - Query Protocol: GameSpy Port+10 - Type: Server Rules
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpyPortPlus10Rules()
  {
    return $this->_GameSpyRules();
  }
  // }}}

  // {{{ _GameSpyPortPlus10Players() - Query Protocol: GameSpy Port+10 - Type: Players
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpyPortPlus10Players()
  {
    return $this->_GameSpyPlayers();
  }
  // }}}

  // {{{ _GameSpyPortPlus10AutoDetect() - Query Protocol: GameSpy Port+10 - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _GameSpyPortPlus10AutoDetect()
  {
    $this->_queryport = $this->_port + 10;
    return TRUE;
  }
  // }}}

  // {{{ _GameSpyPortPlus10FullInfo() - Query Protocol: GameSpy Port+10 - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  // @access protected
  function _GameSpyPortPlus10FullInfo()
  {
    return $this->_GameSpyFullInfo();
  }
  // }}}
  // }}}

  // {{{ Query Protocol: GameSpy2
  // {{{ _GameSpy2Main() - Query Protocol: GameSpy2 - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy2Main()
  {
    $recv   = "";
    $prefix = "";
    $tosend = "";
    $data   = array();

    $tosend  = "\xFE\xFD\x00\x04\x05\x06\x07";
    $tosend .= (array_search("Details", $this->_gettypes) !== FALSE) ? "\xFF" : "\x00";
    $tosend .= (array_search("Players", $this->_gettypes) !== FALSE) ? "\xFF" : "\x00";
    $tosend .= (array_search("Teams",   $this->_gettypes) !== FALSE) ? "\xFF" : "\x00";
    $tosend .= "\x00\x00\x00";

    $this->_CreateUDPSocket();
    $this->_SendSocket($tosend);
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryHeader($recv, "\x00\x04\x05\x06\x07", $prefix))
      return FALSE;

    if (substr($recv, 0, 8) == "splitnum")
      $recv = substr($recv, 11);

    foreach ($this->_gettypes as $type)
    {
      $data[$type] = call_user_func_array(array(&$this, "_".$this->_protocol.$type), array(&$recv));
      if ($this->ERROR)
        return FALSE;
    }
    $this->_CloseUDPSocket();

    return $data;
  }
  // }}}

  // {{{ _GameSpy2Details() - Query Protocol: GameSpy2 - Type: Server Details
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy2Details(&$recv)
  {
    $key  = "";
    $data = array();

    while (($key = $this->_GetCharacterTerminatedString($recv, "\x00")) != "")
      $data[$key] = $this->_GetCharacterTerminatedString($recv, "\x00");

    if ($recv{0} == "\x00")
      $recv = substr($recv, 1);

    return $data;
  }
  // }}}


  // {{{ _GameSpy2Players() - Query Protocol: GameSpy2 - Type: Players
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy2Players(&$recv)
  {
    $tmp     = "";
    $item    = 0;
    $counter = 0;
    $keys    = array();
    $data    = array();

    if (strlen($recv) == 0)
      return $data;

    $data["PlayerCount"] = $this->_GetByteAsAscii($recv);
    $tmp = $this->_GetByteAsAscii($recv);
    if ($tmp == ord("p"))
      $recv = chr($tmp).$recv;
    else
      $data["PlayerCount"] += $tmp;

    while (($tmp = $this->_GetCharacterTerminatedString($recv, "\x00")) != "")
    {
      if (substr($tmp, strlen($tmp) - 1) == "_")
        $tmp = substr($tmp, 0, strlen($tmp) - 1);
      array_push($keys, $tmp);
    }

    while (($tmp = $this->_GetCharacterTerminatedString($recv, "\x00")) != "")
    {
      $data[$counter][$keys[$item]] = $tmp;
      $item++;
      if ($item == count($keys))
      {
        $item = 0;
        $counter++;
      }
    }

    return $data;
  }
  // }}}

  // {{{ _GameSpy2Teams() - Query Protocol: GameSpy2 - Type: Teams
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy2Teams(&$recv)
  {
    $tmp     = "";
    $item    = 0;
    $counter = 0;
    $keys    = array();
    $data    = array();

    if (strlen($recv) == 0)
      return $data;

    $numrules = $this->_GetByteAsAscii($recv);
    while (($tmp = $this->_GetCharacterTerminatedString($recv, "\x00")) != "")
    {
      if (substr($tmp, strlen($tmp) - 1) == "_")
        $tmp = substr($tmp, 0, strlen($tmp) - 1);
      array_push($keys, $tmp);
    }

    while (($tmp = $this->_GetCharacterTerminatedString($recv, "\x00")) != "")
    {
      $data[$counter][$keys[$item]] = $tmp;
      $item++;
      if ($item == count($keys))
      {
        $item = 0;
        $counter++;
      }
    }

    return $data;
  }
  // }}}

  // {{{ _GameSpy2AutoDetect() - Query Protocol: GameSpy2 - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _GameSpy2AutoDetect()
  {
    $this->_queryport = 23000;
    return TRUE;
  }
  // }}}

  // {{{ _GameSpy2FullInfo() - Query Protocol: GameSpy2 - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  function _GameSpy2FullInfo()
  {
    $this->SetRequestData(array("Details", "Players", "Teams"));
    return $this->_GameSpy2Main();
  }
  // }}}
  // }}}

  // {{{ Query Protocol: GameSpy3
  // {{{ _GameSpy3Main() - Query Protocol: GameSpy3 - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy3Main($challenge = false)
  {
    $recv       = "";
    $recv_arr   = array();
    $recv_arr2  = array();
    $prefix     = "";
    $tosend     = "";
    $data       = array();
    $end        = 0;
    $identifier = "\x04\x05\x06\x07";
    $chall_str  = "";

    $this->_CreateUDPSocket();

    if ($challenge)
    {
      $tosend = "\xFE\xFD\x09" . $identifier;
      $this->_SendSocket($tosend);
      $recv = implode("", $this->_GetSocketDataNr(1));
      if (!$this->_CheckQueryHeader($recv, "\x09" . $identifier, $prefix))
        return FALSE;
      $chall_str = pack("N", (int)$recv);
    }

    $tosend  = "\xFE\xFD\x00" . $identifier . $chall_str;
    $tosend .= (array_search("Details", $this->_gettypes) !== FALSE) ? "\xFF" : "\x00";
    $tosend .= (array_search("Players", $this->_gettypes) !== FALSE) ? "\xFF" : "\x00";
    $tosend .= (array_search("Teams",   $this->_gettypes) !== FALSE) ? "\xFF" : "\x00";
    $tosend .= "\x01";
    $this->_SendSocket($tosend);

    while(!$end)
    {
      $recv = implode("", $this->_GetSocketDataNr(1));
      if (!$this->_CheckQueryHeader($recv, "\x00" . $identifier, $prefix))
        return FALSE;

      $index = 0;
      if (substr($recv, 0, 8) != "splitnum")
      {
        $end = 1;
      }
      else
      {
        $recv = substr($recv, 9);
        $index = ord($recv{0});
        if (ord($recv{0}) > ord("\x7F"))
        {
          $end = 1;
          $index = count($recv_arr);
        }
      }

      $recv_arr[$index] = substr($recv, 1);
    }

    $recv = "";
    for ($i = 0; $i < count($recv_arr); $i++)
    {
      # we currently ignore the first byte
      $recv_arr[$i] = substr($recv_arr[$i], 1);
      $start = 0;
      $end   = strlen($recv_arr[$i]);

      $recv_tmp = $recv_arr[$i];

      # check head
      $starttmp = $this->_GetCharacterTerminatedString($recv_tmp, "\x00");
      if (substr($starttmp, -1) == "_")
        $start = strlen($starttmp) + 2;

      # check body
      if (substr($recv_tmp, -2) != "\x00\x00")
      {
        $j = 0;
        for($j = 2; (substr($recv_tmp, strlen($recv_tmp) - $j, 1) != "\x00" || $j == strlen($recv_tmp)); $j++);
        $end = -$j + 1;
      }

      $recv .= substr($recv_arr[$i], $start, $end);
    }

    foreach ($this->_gettypes as $type)
    {
      $data[$type] = call_user_func_array(array(&$this, "_".$this->_protocol.$type), array(&$recv));
      if ($this->ERROR)
        return FALSE;
    }
    $this->_CloseUDPSocket();

    return $data;
  }
  // }}}

  // {{{ _GameSpy3Details() - Query Protocol: GameSpy2 - Type: Server Details
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy3Details(&$recv)
  {
    return $this->_GameSpy2Details($recv);
  }
  // }}}

  // {{{ _GameSpy3Players() - Query Protocol: GameSpy3 - Type: Players
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy3Players(&$recv)
  {
    $data = $this->_GameSpy2Players($recv);
    unset($data["PlayerCount"]);
    return $data;
  }
  // }}}

  // {{{ _GameSpy3Teams() - Query Protocol: GameSpy3 - Type: Teams
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy3Teams(&$recv)
  {
    $tmp     = "";
    $item    = 0;
    $counter = 0;
    $data    = array();

    if (strlen($recv) == 0)
      return $data;

    while(strlen($recv))
    {
      $key = $this->_GetCharacterTerminatedString($recv, "\x00");
      $this->_GetCharacterTerminatedString($recv, "\x00");
      if (substr($key, -1) == "_")
        $key = substr($key, 0, -1);

      $counter = 0;
      while (($tmp = $this->_GetCharacterTerminatedString($recv, "\x00")) != "")
      {
        $data[$counter][$key] = $tmp;
        $counter++;
      }
    }

    return $data;
  }
  // }}}

  // {{{ _GameSpy3AutoDetect() - Query Protocol: GameSpy3 - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _GameSpy3AutoDetect()
  {
    $this->_queryport = $this->_port + 13333;
    return TRUE;
  }
  // }}}

  // {{{ _GameSpy3FullInfo() - Query Protocol: GameSpy3 - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  // @access protected
  function _GameSpy3FullInfo()
  {
    $this->SetRequestData(array("Details", "Players", "Teams"));
    return $this->_GameSpy3Main(false);
  }
  // }}}
  // }}}

  // {{{ Query Protocol: GameSpy4
  // {{{ _GameSpy4Main() - Query Protocol: GameSpy4 - Type: Main Methode
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy4Main()
  {
    return $this->_GameSpy3Main(true);
  }
  // }}}

  // {{{ _GameSpy4Details() - Query Protocol: GameSpy2 - Type: Server Details
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy4Details(&$recv)
  {
    return $this->_GameSpy3Details($recv);
  }
  // }}}

  // {{{ _GameSpy4Players() - Query Protocol: GameSpy4 - Type: Players
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy4Players(&$recv)
  {
    return $this->_GameSpy3Players($recv);
  }
  // }}}

  // {{{ _GameSpy4Teams() - Query Protocol: GameSpy4 - Type: Teams
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _GameSpy4Teams(&$recv)
  {
    return $this->_GameSpy3Teams($recv);
  }
  // }}}

  // {{{ _GameSpy4AutoDetect() - Query Protocol: GameSpy4 - Type: AutoDetect Methode
  // @return bool  always TRUE
  // @access protected
  function _GameSpy4AutoDetect()
  {
    return $this->_GameSpy3AutoDetect();
  }
  // }}}

  // {{{ _GameSpy4FullInfo() - Query Protocol: GameSpy4 - Type: Request Full Server Info
  // @return array  decoded data received from gameserver
  // @access protected
  function _GameSpy4FullInfo()
  {
    $this->SetRequestData(array("Details", "Players", "Teams"));
    return $this->_GameSpy3Main(true);
  }
  // }}}
  // }}}
  // }}}
}

/* {{{ Description
 * RCon Query Protocol Class
 *
 * Adds RCon Support to the Gameserver Query Class
 *
 * Supported Protocols:
 *   - Half-Life ("HalfLife")
 *
 * Users:
 *   $this->SetRConPassword("rconpassword")
 *     ... rcon password to use for query
 *
 *   $this->SetRConCommand("command")
 *     ... rcon command to send
 *
 *   $this->SetRequestData(array("RCon"))
 *     ... "RCon" is the only available requestdata type
 *
 * Developers:
 *   This class extends the gameserver query protocol
 *   If you want to add your own rcon query, the only needed method must be named like:
 *     _RConYourNameMain() ... methode will be called automatically from engine
 *
 }}} */

class RCon extends GSQuery
{
  // {{{ global variable definitions
  var $_rconcommand;
  var $_rconpassword;
  // }}}

  // {{{ RCon() - main class constructor
  function RCon()
  {
    $_rconcommand  = "";
    $_rconpassword = "";
    $this->GSQuery();
    return TRUE;
  }
  // }}}

  // {{{ SetProtocol() - sets query protocol to use
  // @param  string $arg1  protocol to use for gameserver query
  // @return bool          always TRUE
  // @access public
  function SetProtocol($string)
  {
    GSQuery::SetProtocol("RCon".$string);
    return TRUE;
  }
  // }}}

  // {{{ SetRConCommand() - sets rcon command to send
  // @param  string $arg1  rcon command to send to the gameserver
  // @return bool          always TRUE
  // @access public
  function SetRconCommand($string)
  {
    $this->_rconcommand = $string;
    trigger_error("<b>Set RCon Command</b>: ".$string);
    return TRUE;
  }
  // }}}

  // {{{ SetRConPassword() - sets rcon password
  // @param  string $arg1  rcon password to use for rcon query
  // @return bool          always TRUE
  // @access public
  function SetRConPassword($string)
  {
    $this->_rconpassword = $string;
    trigger_error("<b>Set RCon Password</b>: ".$string);
    return TRUE;
  }
  // }}}

  // {{{ _CheckSettings() - check for valid settings
  // @return bool  FALSE if an error occur
  //               TRUE otherwise
  // @access protected
  function _CheckSettings()
  {
    GSQuery::_CheckSettings();

    if ($this->_rconcommand == "")
      $this->_SoftError("No RCon Command set");
    if ($this->_rconpassword == "")
      $this->_SoftError("No RCon Password set");

    if ($this->ERROR)
      return FALSE;

    return TRUE;
  }
  // }}}

  // {{{ RCon Protocol: HalfLife
  // {{{ _RConHalfLifeMain() - RCon Protocol: HalfLife
  // @return array  decoded data received from gameserver
  //         bool   FALSE if an error occur
  // @access protected
  function _RConHalfLifeMain()
  {
    $recv  = "";
    $chnum = 0;
    $data  = array();

    $this->_CreateUDPSocket();
    $this->_SendSocket("\xFF\xFF\xFF\xFFchallenge rcon\x00");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFF\xFF\xFFchallenge rcon ", $prefix))
      return FALSE;

    $chnum = substr($recv, 0, strlen($recv) - 2);
    $this->_SendSocket("\xFF\xFF\xFF\xFFrcon $chnum ".$this->_rconpassword." ".$this->_rconcommand."\n");
    $recv = implode("", $this->_GetSocketDataNr(1));

    if (!$this->_CheckQueryHeader($recv, "\xFF\xFF\xFF\xFF", $prefix))
      return FALSE;

    $data["RCon"] = substr($recv, 1, strlen($recv) - 3);

    if ($data["RCon"] == "Bad rcon_password." || $data["RCon"] == "Bad challenge.")
    {
      $this->_SoftError("RCon Protocol: Half-Life - ".$data["RCon"]);
      return FALSE;
    }

    $this->_CloseUDPSocket();

    return $data;
  }
  // }}}
  // }}}
}
