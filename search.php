<?php
date_default_timezone_set("Asia/Tokyo");
mb_regex_encoding("UTF-8");
mb_language("Japanese");
mb_internal_encoding("UTF-8");
setlocale(LC_ALL, 'ja_JP.UTF-8');
mb_regex_encoding("UTF-8");

//------------------------------------------------------------
//.htmlファイルの探索の最上位フォルダ
$basedir=__DIR__.DIRECTORY_SEPARATOR;

//上記フォルダのURL
$filepath = pathinfo($_SERVER["REQUEST_URI"]);
$baseurl=substr($_SERVER["REQUEST_URI"],0,strlen($_SERVER["REQUEST_URI"])-strlen($filepath['basename']));

//検索ページのタイトル
$search_name='サイト内検索';
//検索ページからトップページへのリンクURL
$topurl='/';
//------------------------------------------------------------

$word='';
if(isset($_REQUEST["q"])){
  $word=$_REQUEST["q"];
  $word=trim(mb_convert_encoding($word,"UTF-8","UTF-8,SJIS,JIS,EUC-JP"));
  $word=mb_convert_kana($word,'aKsV','UTF-8');
  $word=htmlentities($word);
}

$html_head=<<<EOT

<!DOCTYPE html>
<html>
  <head>
    <title>検索結果「{$word}」 - Github.io</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css" />
    <meta name="viewport" content="width=device-width">
  </head>
  <body>
    <div class="container">
        <div class="side" id="pc">
            <form action="search.php" method="get">
                <input type="search" autocomplete="off" name="q" value="{$word}" placeholder="検索キーワードを入力してください">
                <input type="submit" value="検索">
            </form>
            <img src="jexco.png" width="150">
            <h2>概要</h2>
            <p>マイクラ高速とかを作ったり、コード(HTML,PHP,CSS,JS,Pyなど)を見たり書いたりしている人です。</p>
        </div>
        <div class="main">
        EOT;
        $html_header=<<<EOT
        <h1>{$search_name}</h1>
        <main>
        EOT;
        $html_back=<<<EOT
        EOT;
        $html_form=<<<EOT
        
        EOT;
        $html_foot =<<<EOT
        </main>
        </div>
        </div>
        <div id="sp" class="side-sp">
            <form action="search.php" method="get">
                <input type="search" autocomplete="off" name="q" value="{$word}" placeholder="検索キーワードを入力してください">
                <input type="submit" value="検索">
            </form>
            <img src="jexco.png" width="150">
            <h2>概要</h2>
            <p>マイクラ高速とかを作ったり、コード(HTML,PHP,CSS,JS,Pyなど)を見たり書いたりしている人です。</p>
        </div>
  </body>
</html>

EOT;


if(!isset($word)||$word==""){
  echo $html_head;
  echo $html_header;
  echo $html_back;
  echo $html_form;
  echo $html_foot;
}else{
  $word_arr=mb_split(" ",$word);
  for($i=count($word_arr)-1;$i>=0;$i--){
    if($word_arr[$i]==""){
      array_splice($word_arr,$i,1);
    }
  }
  $files=[];
  //.htmlファイルの探査
  searchHtmlFile($basedir,$baseurl,$files);

  $matchFiles=[];

  $html="";
  foreach($files as $file){
    $buf="";
    $buf=file_get_contents($file["path"]);
    $buf=@mb_convert_encoding($buf,"UTF-8","ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN,SJIS");
    $buf=str_replace(["\r\n","\r","\n"],'',$buf);
    $buf=str_replace([' ','　',"\t"],'',$buf);

    if(preg_match("/<title[^>]*?>(.*?)<\/title>/i", $buf, $tmp)==1){
      $title=trim($tmp[1]);
    }else{
      $title="";
    }

    $flag=false;
    if(preg_match("/<body[^>]*?>(.*?)<\/body>/i", $buf, $tmp)==1){
      $body=$tmp[1];
      $flag=true;
    }
    if($flag){
      $body = preg_replace('/<style.*?>.*?<\/style.*?>/is', '', $body) ;
      $body = preg_replace('/<script.*?>.*?<\/script.*?>/is', '', $body) ;
      $body = preg_replace('/<!--.*?-->/is', '', $body) ;
      $body=strip_tags($body);
      //$body=html_entity_decode($body);
      $body=mb_convert_kana($body,'aKSV','UTF-8');

      $count=0;
      foreach($word_arr as $val){
        if(mb_stripos($title.$body,$val)===false){
          $flag=false;
          break;
        }else{
          //$count+=mb_substr_count($title.$body,$val);
        }
      }
      if($flag){
        $pos=mb_stripos($title.$body,$word_arr[0]);
        $l=mb_strlen($word_arr[0]);
        $pos1=$pos-50;
        if($pos1<0){$pos1=0;}
        $pos2=$pos+$l+50;
        if($pos2>(mb_strlen($body))){$pos2=mb_strlen($body);}
        $body_m=mb_substr($body,$pos1,$pos2-$pos1);

        foreach($word_arr as $val){
          $body_m=str_ireplace($val,'<span class="despbold">'.$val.'</span>',$body_m);
        }
        if($title==""){$title=mb_substr($body,0,10); /*$title="タイトル無";*/}

        $matchFile=[];
        $matchFile["url"]=$file["url"];
        $matchFile["title"]=$title;
        $matchFile["body"]=$body_m;
        $matchFile["name"]=$file["name"];
        $matchFile["date"]=$file["date"];
        //$matchFile["count"]=$count;
        $matchFiles[]=$matchFile;
      }
    }
  }
  //ファイルの更新日順に並べる
  $order=[];
  foreach($matchFiles as $val){
    $order[]=$val["date"];
  }
  arsort($order);
  $results=[];
  foreach($order as $key=>$val){
    $results[]=$matchFiles[$key];
  }

  echo $html_head;
  echo $html_header;
  echo $html_back;
  echo $html_form;

  echo "<h4>";
  foreach($word_arr as $wd){echo "「".$wd."」";}
  echo "の検索結果:".count($matchFiles)."件</h4>"."\n";
  if(count($matchFiles)>0){
    foreach($results as $result){
      echo '<div class="search_result">'."\n";
      echo '  <div><a style="" href="'.$result["url"].'">'.$result["title"].'</a></div>'."\n";
      echo '  <div>'."\n";
      echo '    <p class="datefmt">更新日:<time>'.$result["date"].'</time><span class="url">'.$result["url"].'</span></p>'."\n";
      echo '    <p class="hit_text">'.$result["body"].'</p>'."\n";
      echo '  </div>'."\n";
      echo "</div>\n";
    }
  }else{
    echo "<p>見つかりませんでした</p>\n";
  }
  echo $html_back;
  echo $html_foot;
}

//.htmlファイルの再帰探査
function searchHtmlFile($basedir,$baseurl,&$files){
  foreach( new DirectoryIterator($basedir) as $fileinfo ) {
    if($fileinfo->isDir()){
      if($fileinfo->getFilename() !=='.' && $fileinfo->getFilename() !== '..'){
        searchHtmlFile(
          $basedir.$fileinfo->getFilename().DIRECTORY_SEPARATOR,
          $baseurl.$fileinfo->getFilename().'/',
          $files
        );
      }
    }else{
      if($fileinfo->getExtension()==='html'){
        $file=[];
        $file["path"]=$basedir.$fileinfo->getFilename();
        $file["url"]=$baseurl.$fileinfo->getFilename();
        $file["name"]=$fileinfo->getFilename();;
        $file["date"]=Date("Y/m/d H:i:s",$fileinfo->getMTime());
        $files[]=$file;
      }
    }
  }
}
?>
