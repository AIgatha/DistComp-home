<?php
### Library for PHP Forked Computation
###
### Version: 3.3, for Linux
###          
### Author : Prof. Wei-Cheng Lo,
###          Department of Biological Science and Technology,
###          National Chiao Tung University,
###          Taiwan, R.O.C.
###
### Contact: WadeLo@nctu.edu.tw
###
### Major Updates: 2013.06.16, 2014.03.07, 2014.09.19, 2015.02.14
###                2015.10.25, 2016.03.02,
###                2017.12.27, 2018.01.18, 2018.03.25





##### Preparations

$sys_tmpdir = "/dev/shm";
if ( !@Is_Writable($sys_tmpdir) ) $sys_tmpdir = Sys_Get_Temp_Dir();
if ( !@Is_Writable($sys_tmpdir) ) $sys_tmpdir = ".";

$distCompHome_count = 0;
while ( !@Is_Writable($GLOABL_DISTCOMPHOME_TMPDIR) && $distCompHome_count++ < 10 )
{
  $GLOBAL_DISTCOMP_TMPKEY     = Hash( "crc32b", DistCompHome_TmpID() );
  $GLOABL_DISTCOMPHOME_TMPDIR = "$sys_tmpdir/DistComp@Home_$GLOBAL_DISTCOMP_TMPKEY";
  
  if ( !File_Exists($GLOABL_DISTCOMPHOME_TMPDIR) )
  {
    @MkDir($GLOABL_DISTCOMPHOME_TMPDIR, 0777, TRUE);
    @Chmod($GLOABL_DISTCOMPHOME_TMPDIR, 0777);
  }
} //while

if ( !@Is_Writable($GLOABL_DISTCOMPHOME_TMPDIR) ) $GLOABL_DISTCOMPHOME_TMPDIR = ".";
if ( !@Is_Writable($GLOABL_DISTCOMPHOME_TMPDIR) ) $GLOABL_DISTCOMPHOME_TMPDIR = FALSE;
ClearStatCache();

if ($GLOABL_DISTCOMPHOME_TMPDIR != ".") @RmDir($GLOABL_DISTCOMPHOME_TMPDIR);





##### Accessory functions

function DistCompHome_TmpID()
{
  return Uniqid((string)Rand(100000, 999999) . "-", TRUE);
} //function DistCompHome_TmpID



function DistCompHome_Array_Interlaced_Chunk($Arr, $numSubsets)
{
  if ( !Is_Array($Arr) ) return FALSE;

  $RET = Array();
  $data_subset_idx = -1;
  foreach ($Arr as $key=>$val)
  {
    if (++$data_subset_idx == $numSubsets) $data_subset_idx = 0;
    $RET[$data_subset_idx][$key] = $val;
  } //foreach $Arr

  return $RET;
} //function DistCompHome_Array_Interlaced_Chunk



function DistCompHome_Save_Var($varName, $varValue)
{
  global $GLOBAL_DISTCOMP_TMPKEY, $GLOABL_DISTCOMPHOME_TMPDIR;
  if (!$GLOABL_DISTCOMPHOME_TMPDIR) return FALSE;

  $varFile = "$GLOABL_DISTCOMPHOME_TMPDIR/SavedVar_{$GLOBAL_DISTCOMP_TMPKEY}_" . MD5($varName) . ".tmp";

  if ( !File_Exists($GLOABL_DISTCOMPHOME_TMPDIR) )
  {
    @MkDir($GLOABL_DISTCOMPHOME_TMPDIR, 0777, TRUE);
    @Chmod($GLOABL_DISTCOMPHOME_TMPDIR, 0777);
  }
  $saved = File_Put_Contents( $varFile, GzCompress( Serialize($varValue), 1 ) );
  @Chmod($varFile, 0777);

  return $saved;
} //function DistCompHome_Save_Var



function DistCompHome_Get_Var($varName)
{
  global $GLOBAL_DISTCOMP_TMPKEY, $GLOABL_DISTCOMPHOME_TMPDIR;
  if (!$GLOABL_DISTCOMPHOME_TMPDIR) return FALSE;

  $varFile = "$GLOABL_DISTCOMPHOME_TMPDIR/SavedVar_{$GLOBAL_DISTCOMP_TMPKEY}_" .MD5($varName). ".tmp";
  $fileContents = @File_Get_Contents($varFile);
  if ($fileContents === FALSE) return NULL;

  $varValue = Unserialize( GzUncompress($fileContents) );
  @Unlink($varFile);

  return $varValue;
} //function DistCompHome_Get_Var





##### DistComp with PHP Fork Function

function DistCompHome( $Data,
                       $ElementVarInfo = Array("var_elementKey", "var_elementVal"),
                       $script,
                       $var_results = "R",
                       $Vars = Array(),
                       $np = 2,
                       $if_keeping_elements_order = TRUE )
{
  global $GLOBAL_DISTCOMP_TMPKEY, $GLOABL_DISTCOMPHOME_TMPDIR;
  if (!$GLOABL_DISTCOMPHOME_TMPDIR) return FALSE;


  ### Check Params
  ## Input data, script, variables and np (number of processes)
  $np = (int)$np;

  if ( !Is_Array($Data) || !Is_String($script) || !Is_Array($Vars) || $np < 0 ) return FALSE;

  $Vars_Processed = Array();
  foreach ($Vars as $varName=>$varValue)
  {
    if ($varName[0] === '$') $varName = SubStr($varName, 1);
    $Vars_Processed[$varName] = $varValue;
  }
  Unset($Vars);

  if ( Count($Vars_Processed) > 0 && Extract($Vars_Processed) < 1 ) return FALSE;
  Unset($Vars_Processed);

  if ($np < 1) $np = 1;

  ## $ElementVarInfo, e.g., '$key=>$val'
  #
  if ( Is_Array($ElementVarInfo) )
  {
    if ( Count($ElementVarInfo) < 2 ) return FALSE;
    $ElementVarInfo = Array_Slice($ElementVarInfo, 0, 2);
    $ElementVarInfo = Implode("=>", $ElementVarInfo);
  } //if

  if ( !Is_String($ElementVarInfo) ) return FALSE;
  $ElementVarInfo = Str_Replace('$', '', $ElementVarInfo); //remove "$", preserve only var names

  #
  if ( StrPos($ElementVarInfo, "=>") === FALSE ) $ElementVarInfo = "var_elementKey=>$ElementVarInfo";

  List($var_elementKey, $var_elementVal) = Explode("=>", $ElementVarInfo);

  ## The variable for collecting results
  $var_results = Str_Replace('$', '', $var_results);


  ### Determine Results' Order
  // $if_keeping_elements_order can be¡G
  // TRUE/"key"/"keys"      => When the keys of the result array are the keys of $Data, order results by $Data keys
  // "val"/"value"/"values" => When the keys of the result array are the values of $Data, order results by $Data values
  // FALSE                  => No sort
  if ( !Is_Bool($if_keeping_elements_order) )
    $if_keeping_elements_order = StrToLower((string)$if_keeping_elements_order);

  if     ( $if_keeping_elements_order    === TRUE
           || $if_keeping_elements_order == "key"
           || $if_keeping_elements_order == "keys" )
  {
    $Orders = Array_Keys($Data);
  }
  elseif ( $if_keeping_elements_order    == "val"
           || $if_keeping_elements_order == "value"
           || $if_keeping_elements_order == "values" )
  {
    $Orders = Array_Values($Data);
  }
  else
  {
    $if_keeping_elements_order = $Orders = FALSE;
  }


  ### Make the script container 
  $script = Trim($script);


  ### Group the raw data
  $Data_Subsets = ( $np > 1 ? DistCompHome_Array_Interlaced_Chunk($Data, $np) : Array($Data) );


  ### Obtain an ID for saving temporary results
  $tmp_id = DistCompHome_TmpID();


  ### Forked Computation
  $Data_Subset_Idxs = Array_Keys($Data_Subsets);
  //$num_data_subsets = Count($Data_Subset_Idxs);

  $PIDs = Array(); //process IDs
  foreach ($Data_Subset_Idxs as $data_subset_idx)
  {
    ## Make a forked child process
    $pid = -1;
    $t = 0;
    while ( $pid == -1 && $t++ < 300 ) //this loop is to prevent temporary unavailability of system resources
    {
      $pid = @Pcntl_Fork();

      if ($pid == -1) //not forked
      {
        if ( $pid_running = Array_Shift($PIDs) )
        {
          if ( Pcntl_WaitPid($pid_running, $status, WUNTRACED) <= 0 ) Array_UnShift($PIDs, $pid_running);

          foreach ($PIDs as $key_pid_running=>$pid_running)
            if ( Pcntl_WaitPid($pid_running, $status, WNOHANG) > 0 ) Unset($PIDs[$key_pid_running]);
        } //if
        else uSleep(0.2E6); //wait before retrial
      } //if ($pid == -1)
    } //while ( $pid == -1 && $t++ < 300 )

    ## Failed to fork
    if ($pid == -1) return FALSE; //after 300 retrials with failures

    ## Succeeded to fork
    if (!$pid) //in a child
    {
      /* ### child process START ### */

      if ($var_results) $$var_results = Array(); //the variable for collecting results

      //!!! compact, $

      $Elements = $Data_Subsets[$data_subset_idx];
      while ( List($$var_elementKey, $$var_elementVal) = Each($Elements) )
      {
        Unset($Elements[$$var_elementKey]);
        Eval($script);
      } //foreach $ElementKeys

      if ($var_results) //save the results computed by this child to temp (i.e., reporting to the parent)
        Exit( !DistCompHome_Save_Var("$tmp_id-$data_subset_idx", $$var_results) );
      else
        Exit(0);

      /* ### child process END ### */
    } //if ( !$pid )
    else $PIDs[$data_subset_idx] = $pid; //in parent (and forked)
  } //foreach $Data_Subset_Idxs

  ## wait children to be finished
  while ( $pid_running = Array_Shift($PIDs) )
  {
    if ( Pcntl_WaitPid($pid_running, $status, WUNTRACED) <= 0 ) Array_UnShift($PIDs, $pid_running);

    foreach ($PIDs as $key_pid_running=>$pid_running)
      if ( Pcntl_WaitPid($pid_running, $status, WNOHANG) > 0 ) Unset($PIDs[$key_pid_running]);
  } //while


  ### complete parent processing now all children have finished
  // if "results collecting variable" specified
  if ($var_results)
  {
    $RET = Array();
    foreach ($Data_Subset_Idxs as $data_subset_idx)
    {
      $R = DistCompHome_Get_Var("$tmp_id-$data_subset_idx");
      if ($R) $RET += $R;
    }

    if ($GLOABL_DISTCOMPHOME_TMPDIR != ".") @RmDir($GLOABL_DISTCOMPHOME_TMPDIR);

    # If not to sort
    if (!$if_keeping_elements_order) return $RET;

    # Sort
    $Return = Array();
    foreach ($Orders as $order)
      if ( IsSet($RET[$order]) ) { $Return[$order] = $RET[$order]; Unset($RET[$order]); }

    if ($RET) $Return += $RET; //in case there are remaining results in $RET

    return $Return;
  } //if ($var_results)


  // if "results collecting variable" specified, return TRUE for success
  if ($GLOABL_DISTCOMPHOME_TMPDIR != ".") @RmDir($GLOABL_DISTCOMPHOME_TMPDIR);
  return TRUE;
} //function DistCompHome
?>