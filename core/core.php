﻿<?php

// версія ядра

define('VER', '3.2.6');

// масив доступних тем

$THEME_ARRAY = array(
  'white'   => array(
    'name'  => 'White-Orange',
    'src'   => 'theme-white.css',
    'color' => array(
      'FFFFFF',
      '3ba0b3',
      'ff5722',
    ),
  ),
  'dark'   => array(
    'name'  => 'Dark-Yellow',
    'src'   => 'theme-dark.css',
    'color' => array(
      '404040',
      '3ba0b3',
      'ffe44a',
    ),
  ),
  'black-white'   => array(
    'name'  => 'Black-White',
    'src'   => 'theme-black-white.css',
    'color' => array(
      '404040',
      'f2f2f2',
      'ffe44a',
    ),
  ),
);

// підключаємо файл конфігів

error_reporting(0);
define('UPDATE_SERVER', 'http://update.mesija.net/');

if (file_exists('./core/config.php')) {
  include('./core/config.php');
} else {
  if (file_exists('./core/config_sample.php')) {
    rename('./core/config_sample.php', './core/config.php');
    include('./core/config.php');
  } else {
    alert('error', 404, 'Not create file ./core/config.php T_T');
  }
}

// виводимо результат виконання

function alert($type = 'error', $code = 0, $data = 'Undefined error')
{
  $message = array(
    'type' => $type,
    'code' => $code,
    'data' => $data
  );
  exit(json_encode($message));
}

// перевіряємо параметр захисту від випадкових перезавантажень

$lock = 1;
if (isset($_COOKIE['lock'])) {
  $lock = $_COOKIE['lock'];
} else {
  setcookie('lock', $lock);
}

define('LOCK', $lock);

// змінюємо параметр захисту від випадквого перезавантаження

if (isset($_GET['lock'])) {
  setcookie('lock', $_GET['lock']);
  if ($_GET['lock'] == 1) {
    alert('ok', 200, 'Lock reload page ON');
  } else {
    alert('error', 200, 'Lock reload page OFF');
  }
}

// завантажуємо інформацію про тему

$THEME_DATA = array(
  'logo-title'  => $THEME_ARRAY[THEME]['name'],
  'color-array' => $THEME_ARRAY[THEME]['color'],
);

// головний контролер

switch(isset($_POST['action']) ? $_POST['action'] : ''){
  case 'settings-save': // зберігаємо параматри
    $settingsData = "<?php\n";
    foreach(json_decode($_POST['param']) AS $key => $val){
      $settingsData .= "define('" . $key . "', " . $val . ");\n";
    }
    if(file_put_contents('./core/config.php', $settingsData)){
      alert('ok', 200, 'Settings save');
    } else {
      alert('ok', 400, 'Error save settings');
    }
    break;
  case 'check-update':
    $update_time = (int)file_get_contents('./core/update');
    $update_time = $update_time + (60 * 60);
    if ($update_time < time()) {
      $upVer = @file_get_contents(UPDATE_SERVER . 'IDownloader/version');
      if ($upVer) {
        if (version_compare(VER, $upVer)) {
          alert('ok', 200, 'New version available ' . $upVer);
        }
      }
    }
    alert('info', 100, 'This is leather version');
    break;
  case 'update':
    update();
    break;
}

// уплоадимо csv файл в теку

if (isset($_GET['fileUpload'])) {
  $rez = copy($_FILES['file']['tmp_name'], './csv/' . $_FILES['file']['name']);
  if ($rez) {
    alert('ok', 200, 'File uploaded');
  } else {
    alert('error', 400, 'File not uploaded');
  }
}

// стартуємо завантаження

if (isset($_GET['start']) AND !empty($_GET['start'])) {
  $dir = $_GET['start'];
  if (file_exists(DOWNLOAD_FOLDER . '/' . $dir . '/' . $dir . '.csv')) {
    exec('rm -Rf ' . DOWNLOAD_FOLDER . '/' . $dir . '/' . $dir . '.csv');
  }
  alert('ok', 200, 'Start download');
}

// завершуємо завантаження

if (isset($_GET['finish']) AND !empty($_GET['finish'])) {
  $dir = $_GET['finish'];
  exec('chmod 777 -Rf ' . DOWNLOAD_FOLDER . '/' . $dir . '/');
  alert('ok', 200, 'Finish download');
}

// кліримо завантажені данні

if (isset($_GET['clear']) AND !empty($_GET['clear'])) {
  $dir = $_GET['clear'];
  if (file_exists(DOWNLOAD_FOLDER . '/' . $dir . '/')) {
    exec('rm -Rf ' . DOWNLOAD_FOLDER . '/' . $dir . '/');
    alert('ok', 200, 'Folder ' . $dir . ' is delete');
  } else {
    alert('error', 404, 'No such dir ' . DOWNLOAD_FOLDER . '/' . $dir . '/');
  }

}

// видаляємо csv файл

if (isset($_GET['deleteFile']) AND !empty($_GET['deleteFile'])) {
  $file = $_GET['deleteFile'];
  if (file_exists('./' . CSV_FOLDER . '/' . $file)) {
    exec('rm -Rf ./' . CSV_FOLDER . '/' . $file);
  }
  alert('ok', 200, 'File ' . $file . ' is delete');
}

// видаляємо папку

if (isset($_GET['deleteDir']) AND !empty($_GET['deleteDir'])) {
  $dir = $_GET['deleteDir'];
  if (file_exists(DOWNLOAD_FOLDER . '/' . $dir . '/')) {
    exec('rm -Rf ./' . DOWNLOAD_FOLDER . '/' . $dir . '/');
  }
  alert('ok', 200, 'Dir ' . $dir . ' is delete');
}

// завантажуємо файл

if (isset($_POST['s']) AND isset($_POST['t']) AND isset($_POST['dir'])) {
  $_POST['s'] = base64_decode($_POST['s']);
  $_POST['t'] = base64_decode($_POST['t']);
  $dir = $_POST['dir'];
  $d = str_replace(basename($_POST['t']), '', $_POST['t']);
  if (!file_exists(DOWNLOAD_FOLDER . '/' . $dir . '/' . $d)) {
    mkdir(DOWNLOAD_FOLDER . '/' . $dir . '/' . $d, 0777, true);
  }
  if (!file_exists(DOWNLOAD_FOLDER . '/' . $dir . '/' . $_POST['t'])
    OR filesize(DOWNLOAD_FOLDER . '/' . $dir . '/' . $_POST['t']) == 0
  ) {
    $img = false;
    $img = downloadImage($_POST['s'], $_POST['t'], $dir);
    if ($img) {
      alert('ok', 200, 'Download file ' . $_POST['s'] . ' to ' . $_POST['t'] . ' is ok');
    } else {
      $s = $_POST['s'];
      if (preg_match('/\.(jpeg|JPEG|jpg|JPG)$/', $s, $r)) {
        switch ($r[0]) {
          case '.jpeg':
            $mass = array('JPEG', 'jpg', 'JPG');
            break;
          case '.JPEG':
            $mass = array('jpeg', 'jpg', 'JPG');
            break;
          case '.jpg':
            $mass = array('jpeg', 'JPEG', 'JPG');
            break;
          case '.JPG':
            $mass = array('jpeg', 'JPEG', 'jpg');
            break;
        }
        $s = preg_replace('/' . $r[0] . '$/', '', $s);
        foreach ($mass AS $val) {
          $img = false;
          $img = downloadImage(str_replace(' ', "%20", $s . '.' . $val), $_POST['t'], $dir);
          if ($img) {
            alert('ok', 200, 'Download file ' . $_POST['s'] . ' to ' . $_POST['t'] . ' is ok');
          }
        }
      }
      $file = fopen(DOWNLOAD_FOLDER . '/' . $dir . '/' . $dir . '.csv', 'a');
      $put = array('0', $_POST['ts'], $_POST['s'], $_POST['t']);
      fputcsv($file, $put, ',', '"');
      fclose($file);
      alert('error', 404, 'Error download file ' . $_POST['s'] . ' to ' . $_POST['t']);
    }
  } else {
    alert('ok', 200, 'File ' . $_POST['t'] . ' is exists');
  }
}

// спроба завантажити картинку

function downloadImage($source, $target, $dir){
  $img = @file_get_contents($source);
  if ($img AND !preg_match('/(<html)/', $img)) {
    file_put_contents(DOWNLOAD_FOLDER . '/' . $dir . '/' . $target, $img);
    return true;
  } else {
    if(PROXY_ACTIVE){
      $proxyArray = explode(', ', PROXY_SERVER);
      $proxy = $proxyArray[rand(0, count($proxyArray) - 1)];
      exec('curl -x ' . $proxy . ' --proxy-user ' . PROXY_AUTH . ' -L ' . $source . ' > ' . DOWNLOAD_FOLDER . '/' . $dir . '/' . $target);
      $img = @file_get_contents(DOWNLOAD_FOLDER . '/' . $dir . '/' . $target);
      if ($img AND !preg_match('/(<html)/', $img) AND filesize(DOWNLOAD_FOLDER . '/' . $dir . '/' . $target) > 0) {
        return true;
      } else {
        exec('rm -Rf ' . DOWNLOAD_FOLDER . '/' . $dir . '/' . $target);
      }
    }
  }
  return false;
}

// завантажуємо інформацію з csv файлу

if (isset($_GET['loadFile']) AND !empty($_GET['loadFile']) AND isset($_GET['step']) AND isset($_GET['type'])) {
  $file = $_GET['loadFile'];
  $part = $_GET['step'];
  $max = ($part + 1) * PACK;
  $min = $part * PACK;
  ini_set('max_execution_time', '0');
  ini_set('display_errors', '0');
  if ($_GET['type'] == 0) {
    $url = './' . CSV_FOLDER . '/' . $file;
  } else {
    $url = DOWNLOAD_FOLDER . '/' . $file . '/' . $file . '.csv';
  }
  if (file_exists($url)) {
    $csv = fopen($url, 'r');
    $input = array();
    $i = 0;
    $delimiter = '';
    while ($line = fgets($csv, 9999) AND $i < $max) {
      $line = preg_replace('/[\n\r"]+/', '', $line);
      $mass = array();
      if ($delimiter == '') {
        preg_match_all('/[^a-z0-9A-Z:\/\.?&=_ %\\!#$^+@()<>-]/', $line, $del);
        foreach ($del[0] AS $delTmp) {
          $mass = explode($delTmp, $line);
          if (count($mass) > 3) {
            $delimiter = $delTmp;
            break;
          }
        }
        if ($delimiter == '') {
          alert('error', 404, 'No such delimiter in file ' . $file);
        }
      }
      $mass = explode($delimiter, $line);
      if ($i > $min - 1) {
        if ($mass[6] == 'copied') {
          $mass[6] = 1;
        } else {
          $mass[6] = 0;
        }
        $input[$i][0] = $mass[6];
        $input[$i][1] = base64_encode(str_replace(' ', "%20", $mass[2]));
        $input[$i][2] = base64_encode($mass[3]);
      }
      $i++;
    }
    fclose($csv);
    if (count($input) > 0) {
      alert('ok', 200, json_encode($input));
    } else {
      if ($part == 0) {
        alert('error', 400, 'File ' . $file . ' is empty');
      } else {
        alert('ok', 200, json_encode($input));
      }
    }
  } else {
    alert('error', 404, 'No such file ' . $file);
  }
}

// отримуємо інформацію про міграцію

if (isset($_GET['getInfo']) AND !empty($_GET['getInfo']) AND isset($_GET['type'])) {
  $file = $_GET['getInfo'];
  ini_set('max_execution_time', '0');
  ini_set('display_errors', '0');
  if ($_GET['type'] == 0) {
    $url = './' . CSV_FOLDER . '/' . $file;
  } else {
    $url = DOWNLOAD_FOLDER . '/' . $file . '/' . $file . '.csv';
  }
  if (file_exists($url)) {
    $csv = fopen($url, 'r');
    $line = fgets($csv, 9999);
    $line = str_replace('"', '', $line);
    preg_match_all('/[^a-z0-9A-Z:\/\.?&=_ %\\!#$^+@()<>-]/', $line, $del);
    $mass = array();
    foreach ($del[0] AS $delTmp) {
      $mass = explode($delTmp, $line);
      if (count($mass) > 3) {
        break;
      }
    }
    if (count($mass) < 3) {
      alert('error', 404, 'No such delimiter in file ' . $file);
    }
    alert('error', 404, 'No such store id ' . $mass[1]);
  }
  alert('error', 404, 'No such file ' . $file);
}

// перейменовуємо файл

if (isset($_GET['renameFile']) AND !empty($_GET['renameFile'])) {
  $file = $_GET['renameFile'];
  if (file_exists('./' . CSV_FOLDER . '/' . $file)) {
    if ($_GET['name'] != '') {
      if (!preg_match('/(\.csv)$/', $_GET['name'])) {
        $_GET['name'] .= '.csv';
      }
      if ($_GET['name'] == $file) {
        alert('ok', 201, 'Name file ' . $_GET['name'] . ' is actual');
      }
      if (file_exists('./' . CSV_FOLDER . '/' . $_GET['name'])) {
        alert('error', 401, 'Error! File ' . $_GET['name'] . ' is exists');
      }
      rename('./' . CSV_FOLDER . '/' . $file, './' . CSV_FOLDER . '/' . $_GET['name']);
      alert('ok', 200, array(
        'name' => preg_replace('/\.csv$/', '', $_GET['name']),
        'message' => 'Rename file ' . $file . ' to ' . $_GET['name']
      ));
    } else {
      $csv = fopen('./' . CSV_FOLDER . '/' . $file, 'r');
      $line = fgets($csv, 9999);
      $line = str_replace('"', '', $line);
      preg_match_all('/[^a-z0-9A-Z:\/\.?&=_ %\\!#$^+@()<>-]/', $line, $del);
      $mass = array();
      foreach ($del[0] AS $delTmp) {
        $mass = explode($delTmp, $line);
        if (count($mass) > 3) {
          break;
        }
      }
      if (count($mass) < 3) {
        alert('error', 404, 'No such delimiter in file ' . $file);
      }
      alert('error', 406, 'No connect database');
      $rez = false;
      if ($rez) {
        if ($rez['id'] . '.csv' == $file) {
          alert('ok', 201, 'Name file ' . $rez['id'] . '.csv is actual');
        }
        if (file_exists('./' . CSV_FOLDER . '/' . $rez['id'] . '.csv')) {
          alert('error', 401, 'Error! File ' . $rez['id'] . '.csv is exists');
        }
        rename('./' . CSV_FOLDER . '/' . $file, './' . CSV_FOLDER . '/' . $rez['id'] . '.csv');
        alert('ok', 200, array(
          'name' => $rez['id'],
          'message' => 'Rename file ' . $$file . ' to ' . $rez['id'] . '.csv'
        ));
      } else {
        alert('error', 403, 'Bad store id');
      }
    }
  }
  alert('error', 404, 'No such file ' . $file);
}

// перейменовуємо папку

if (isset($_GET['renameDir']) AND !empty($_GET['renameDir']) AND isset($_GET['name']) AND !empty($_GET['name'])) {
  $file = $_GET['renameDir'];
  if ($file == $_GET['name'])
    alert('info', 300, 'Dir name ' . $file . ' is actual');
  if (is_dir(DOWNLOAD_FOLDER . '/' . $file . '/')) {
    if (!is_dir(DOWNLOAD_FOLDER . '/' . $_GET['name'] . '/')) {
      rename(DOWNLOAD_FOLDER . '/' . $file . '/', DOWNLOAD_FOLDER . '/' . $_GET['name']);
      alert('ok', 200, 'Rename dir ' . $file . ' to ' . $_GET['name']);
    } else {
      alert('error', 400, 'Dir ' . $_GET['name'] . ' is exists!');
    }
  }
  alert('error', 404, 'Dir ' . $file . ' not exists!');
}

// надаємо права

if (isset($_GET['perDir']) AND !empty($_GET['perDir'])) {
  $folder = $_GET['perDir'];
  if (is_dir(DOWNLOAD_FOLDER . '/' . $folder . '/')) {
    exec('chmod 777 -Rf ' . DOWNLOAD_FOLDER . '/' . $folder . '/');
    alert('ok', 200, 'Set permissions dir ' . $folder);
  }
  alert('error', 404, 'Dir ' . $folder . ' not exists!');
}

// перевіряємо оновлення

function update(){
  $fileList = @file_get_contents(UPDATE_SERVER . 'IDownloader/fileList');
  if(!$fileList){
    alert('error', 404, 'Not connect to server');
  }
  $fileList = json_decode($fileList);
  foreach ($fileList AS $file) {
    if ($file[0] == '+') {
      if ($file[2] == "") {
        mkdir('./' . $file[1] . '/', 0777, true);
      } else {
        $fileUpdate = @file_get_contents(UPDATE_SERVER . 'IDownloader/' . $file[1]);
        exec('rm -Rf ' . $file[1] . '.' . $file[2]);
        file_put_contents($file[1] . '.' . $file[2], $fileUpdate);
      }
    } else {
      unlink('./' . ($file[2] != '') ? $file[1] . '.' . $file[2] : $file[1]);
    }
  }
  file_put_contents('./core/update', time());
  alert('ok', 200, 'Update ok');
}

// генеруємо код основної сторінки

if (!file_exists('./' . CSV_FOLDER . '/')) {
  mkdir('./' . CSV_FOLDER . '/', 0777, true);
  $listDir = '';
} else {
  $tmpDir = array_splice(scandir('./' . CSV_FOLDER . '/'), 2, 100);
  $listDir = array();
  foreach ($tmpDir AS $tmp) {
    if (preg_match('/\.csv$/', $tmp)) {
      $f_file = './' . CSV_FOLDER . '/' . $tmp;
      if (substr((filesize($f_file) / 1000000), 0, 4) > 1.00) {
        if (substr((filesize($f_file) / 1000000), 0, 4) > 100.00) {
          $n = 5;
        } else {
          $n = 4;
        }
        $f_size = substr((filesize($f_file) / 1000000), 0, $n) . " Mb";
      } else {
        $f_size = substr((filesize($f_file) / 1000), 0, 5) . " Kb";
      }
      $listDir[$tmp] = $f_size;
    }
  }
  if (empty($listDir)) {
    $listDir = '';
  }
}

if (!file_exists(DOWNLOAD_FOLDER . '/'))
  mkdir(DOWNLOAD_FOLDER . '/', 0777, true);

$tmpDownload = array_splice(scandir(DOWNLOAD_FOLDER . '/'), 2, 100);
$listDownload = array();
foreach ($tmpDownload AS $tmp) {
  if (preg_match('/^[^\.\s]+$/', $tmp)) {
    $listDownload[$tmp] = $tmp;
  }
}
if (empty($listDownload)) {
  $listDownload = '';
}

// генеруємо код контенту

/**
 * @param $listDir
 * @param $listDownload
 * @param $themeData
 */
function printContent($listDir, $listDownload, $themeData)
{
  $ver = explode('.', VER);
  echo '<div class="block logo">
  <b class="icon-download"></b> <div id="logo-img"></div> <v>' . $ver[0] . '<v2>.' . $ver[1] . '.' . $ver[2] . '<v2></v> ' . $themeData['logo-title'] . '
  <button class="reload" onclick="res(1,1,0)"><b class="icon-loop2"></b> Reload</button>
  <button class="settings" id="settings" onclick="settingsOpen()" title="Open settings"><b class="icon-cog"></b></button>
  <button class="lock' . (LOCK ? '' : ' lock-off') . '" id="lock" onclick="lock()" title="Lock reload page"><b class="icon-lock"></b></button>
  <button class="add" id="add" onclick="addFile()" title="Upload new file"><b class="icon-box-add"></b></button>
  </div>
  <div class="block download panel">
    <div id="left">
        <div class="all">
            <div class="left"></div>
            <div class="right"></div>
        </div>
        <div class="failed">
            <div class="left"></div>
            <div class="right"></div>
        </div>
        <div class="copied">
            <div class="left"></div>
            <div class="right"></div>
        </div>
    </div>
    <div id="right">
        <div class="process">
            <div class="left"><b>0</b> <span class="icon-meter"></span></div>
            <div class="right"><button onclick="add(10,0)" class="dis">+10 process</button></div>
        </div>
        <div class="only">
            <div class="center"><button onclick="check()"><span class="icon-aid"></span> <span id="only_failed">Only failed</span></button></div>
        </div>
    </div>
  </div>
  <div class="block download cir"><div id="topLoader" class="topLoader"></div></div>
  <div class="block fileList">
    <div id="csv">
      <h1><span class="icon-file3"></span> CSV file</h1>';
  if (!is_array($listDir))
    echo $listDir;
  else {
    echo '<table class="csvFolder"><tbody><tr>';
    $step = 1;
    foreach ($listDir AS $name => $size) {
      if ($step == 0) {
        $step++;
        $class = '';
      } else {
        $step--;
        $class = ' step';
      }
      echo '
                <tr class="file-' . preg_replace('/\.csv$/', '', $name) . $class . '">
                  <td onclick="openFile(\'' . $name . '\',0,0)" class="csvFolderName" title="Open file">' . preg_replace('/\.csv$/', '', $name) . '</td>
                  <td class="fileSize" title="Size csv file ' . $size . '">' . $size . '</td>
                  <td class="fileDate" title="Last edit date ' . date("H:i d-m-y", filemtime(CSV_FOLDER . '/' . $name)) . '">' . date("d-m-y", filemtime(CSV_FOLDER . '/' . $name)) . '</td>
                  <td class="icon" onclick="renameFile(\'' . $name . '\')" title="Rename file"><span class="icon-pencil2"></span></td>
                  <td class="icon" onclick="clearLast(\'' . $name . '\')" title="Clear last download files"><span class="icon-magnet"></span></td>
                  <td class="icon delete" onclick="deleteFile(\'' . $name . '\')" title="Delete csv file"><span class="icon-remove"></span></td>
                </tr>';
    }
    echo '</tbody></table>';
  }
  echo '
    </div
      ><div id="folder">
      <h1><span class="icon-folder"></span> Download folder</h1>' . '';
  if (!is_array($listDownload))
    echo $listDownload;
  else {
    if (is_array($listDir)) {
      echo '<table class="csvFolder"><tbody><tr>';
      $step = 1;
      foreach ($listDir AS $name => $tmp) {
        if ($step == 0) {
          $step++;
          $class = '';
        } else {
          $step--;
          $class = 'step';
        }
        $name = preg_replace('/\.csv$/', '', $name);
        if (isset($listDownload[$name])) {
          $pre = substr(sprintf('%o', fileperms(DOWNLOAD_FOLDER . '/' . $name)), -3);
          if ($pre != 777) $lock = 'icon-close'; else $lock = 'icon-checkmark';
          echo '
                <tr class="' . $class . '">
                  <td class="csvFolderName">' . $name . '</td>';
          if (file_exists(DOWNLOAD_FOLDER . '/' . $name . '/' . $name . '.csv')) {
            echo '<td class="icon" onclick="openFile(\'' . $name . '\',0,1)"><span class="icon-history" title="Open only failed files in last download"></span></td>';
          } else {
            echo '<td class="dis"></td>' . '';
          }
          echo '
                  <td class="icon" onclick="renameDir(\'' . $name . '\')"><span class="icon-pencil2" title="Rename dir ' . $name . '"></span></td>
                  <td class="icon" onclick="perDir(\'' . $name . '\')"><span class="' . $lock . '" title="Permissions ' . $pre . '"></span></td>
                  <td class="icon delete" onclick="deleteDir(\'' . $name . '\')"><span class="icon-remove" title="Delete dir ' . $name . '"></span></td>
                </tr>';
          unset($listDownload[$name]);
        } else {
          echo '
                <tr class="' . $class . ' empty">
                  <td class="csvFolderName"></td>
                  <td class="icon"></td>
                  <td class="icon"></td>
                  <td class="icon"></td>
                  <td class="icon delete"></td>
                </tr>';
        }
      }
      foreach ($listDownload AS $name) {
        if ($step == 0) {
          $step++;
          $class = '';
        } else {
          $step--;
          $class = 'step';
        }
        $pre = substr(sprintf('%o', fileperms(DOWNLOAD_FOLDER . '/' . $name)), -3);
        if ($pre != 777) $lock = 'icon-close'; else $lock = 'icon-checkmark';
        echo '
                <tr class="' . $class . ' red">
                  <td class="csvFolderName">' . $name . '</td>';
        if (file_exists(DOWNLOAD_FOLDER . '/' . $name . '/' . $name . '.csv')) {
          echo '<td class="icon" onclick="openFile(\'' . $name . '\',0,1)"><span class="icon-history" title="Open only failed files in last download"></span></td>';
        } else {
          echo '<td class="dis"></td>' . '';
        }
        echo '
                  <td class="icon" onclick="renameDir(\'' . $name . '\')"><span class="icon-pencil2" title="Rename dir ' . $name . '"></span></td>
                  <td class="icon" onclick="perDir(\'' . $name . '\')"><span class="' . $lock . '" title="Permissions ' . $pre . '"></span></td>
                  <td class="icon delete" onclick="deleteDir(\'' . $name . '\')"><span class="icon-remove" title="Delete dir ' . $name . '"></span></td>
                </tr>';
      }
      echo '</tbody></table>' . '';
    } else {
      echo '<table class="csvFolder"><tbody><tr>' . '';
      $step = 1;
      foreach ($listDownload AS $name) {
        if ($step == 0) {
          $step++;
          $class = '';
        } else {
          $step--;
          $class = ' class="step"';
        }
        $pre = substr(sprintf('%o', fileperms(DOWNLOAD_FOLDER . '/' . $name)), -3);
        if ($pre != 777) $lock = 'icon-close'; else $lock = 'icon-checkmark';
        echo '
                <tr' . $class . '>
                  <td class="csvFolderName">' . preg_replace('/\.csv$/', '', $name) . '</td>
                  <td class="icon" onclick="renameDir(\'' . $name . '\')"><span class="icon-pencil2" title="Rename dir ' . $name . '"></span></td>
                  <td class="icon" onclick="perDir(\'' . $name . '\')"><span class="' . $lock . '" title="Permissions ' . $pre . '"></span></td>
                  <td class="icon delete" onclick="deleteDir(\'' . $name . '\')"><span class="icon-remove" title="Delete dir ' . $name . '"></span></td>
                </tr>';
      }
      echo '</tbody></table>' . '';
    }
  }
  echo '
    </div>
  </div>
  <div class="block download download_migration"><p style="text-align: center">No data</p></div>' . '';
}

// вертаємо код сторінки

if (isset($_GET['getContent']) AND !empty($_GET['getContent'])) {
  printContent($listDir, $listDownload, $THEME_DATA);
  exit();
}

// інклудимо сторінки

include('./core/head.php');
include('./core/body.php');