<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <meta name="description" itemprop="description" content="Port books on your Amazon wishlist to your Goodreads account" />

    <meta name="keywords" itemprop="keywords" content="goodreads amazon wishlist" />

    <link rel="canonical" href="<?php echo $_SERVER['HTTP_HOST']; ?>" />
    <meta property="og:title" content="Amazon Wishlist to Goodreads Transferer" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo $_SERVER['HTTP_HOST']; ?>" />
    <!-- <meta property="og:image" content="http://beerpilgrimage.com/blog/wp-content/uploads/2015/12/bpfacebook-300x300.jpg" /> -->
    <!-- <meta property="og:image:width" content="300" /> -->
    <!-- <meta property="og:image:height" content="300" /> -->
    <meta property="og:site_name" content="Amazon Wishlist to Goodreads Transferer" />
    <meta property="og:description" content="Port books on your Amazon wishlist to your Goodreads account" />
    <title>Amazon WishList to GoodReads To-Read Shelf</title>

    <!-- Bootstrap -->
    <link href="css/paper.min.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <?php
    ini_set('xdebug.var_display_max_depth', 10);
    ini_set('xdebug.var_display_max_children', 512);
    ini_set('xdebug.var_display_max_data', 2048);
    ini_set('max_execution_time', 300);
    include 'config.php';
    $key = GOODREADS_KEY;
    $secret = GOODREADS_SECRET;
    include_once(realpath(__DIR__.'/vendor/ethanclevenger91/goodreads-oauth/src/GoodreadsOauth/GoodreadsOauth.php'));
    include_once(realpath(__DIR__.'/includes/functions.php'));
    error_reporting(0);
    session_start(); ?>
    <div class="container">
      <div class="row">
        <div class="col-xs-12 col-md-8 col-md-offset-2">
          <?php
          if(!isset($_SESSION['access_token']) && !isset($_REQUEST['oauth_token'])) { //no access token or oauth token
            $case = 1;
          } else if(!isset($_SESSION['access_token']) && isset($_REQUEST['oauth_token'])) { //oauth token, but no access token
            $case = 2;
          }
          else if(isset($_SESSION['oauth_token']) && isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) { //oauth token not same as request token - forgery!!
            $case = 3;
          } else {
            $access_token = $_SESSION['access_token'];
            $obj = new GoodreadsOauth($key, $secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);
            $content = $obj->doGet('http://www.goodreads.com/api/auth_user');
            if($content == null || $content == 'Invalid OAuth Request') { //authorization failed, probably expired token
              $case = 3;
            } else if(isset($_GET['shelf'])) { //shelf picked, get wishlist
              $case = 5;
            } else if(isset($_GET['wishlist'])) { //wishlist picked, get books
              $case = 6;
            } else if(isset($_GET['addBooks'])) { //add the books!
              $case = 7;
            } else { //good to go, pick shelf
              $case = 4;
            }
          }
          switch($case) {
            case 1: //no request token or access token, get request token and sent user to authorize
              $connection = new GoodreadsOauth($key, $secret);
              $request_token = $connection->getRequestToken();


              $_SESSION['oauth_token']  = $request_token['oauth_token'];
              $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

              $authorize_url = $connection->getLoginURL($request_token, 'http://'.$_SERVER['HTTP_HOST']); ?>

              <?php pageHeader("We'd Start, But...", "You haven't authorized access to your Goodreads"); ?>
              <?php progressBar(0); ?>
              <div style="text-align:center">
                <a class="btn btn-primary" href="<?php echo $authorize_url; ?>">Click Here to Access Goodreads</a>
              </div>
              <?php break;
            case 3: //token is expired
              unset($_SESSION['access_token']); ?>
              <div class="panel panel-danger">
              <div class="panel-heading">Token Busted</div>
              <div class="panel-body">
                There was either a token mismatch, or your token is expired (probably the latter). <a href="http://<?php echo $_SERVER['HTTP_HOST']; ?>">Refresh to reauthorize GoodReads.</a>
              </div>
            </div>
              <?php break;
            case 2: //user has authorized, set access token
              $obj = new GoodreadsOauth($key, $secret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
              $access_token = $obj->getAccessToken($_REQUEST['oauth_verifier']);
              $_SESSION['access_token'] = $access_token;
              unset ($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'], $obj);
            case 4: // all's well, let's pick a shelf
              $obj = new GoodreadsOauth($key, $secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);
              $content = $obj->doGet('http://www.goodreads.com/api/auth_user');
              $xml = simplexml_load_string($content);
              $json = json_encode($xml);
              $json = str_replace('@', '', $json);
              $user = json_decode($json)->user;
              $content = $obj->doGet('http://www.goodreads.com/shelf/list.xml?key='.$key.'&user_id='.$user->attributes->id.'&page=1');
              $xml = simplexml_load_string($content);
              $json = json_encode($xml);
              $json = str_replace('@', '', $json);
              $shelves = json_decode($json)->shelves->user_shelf;
              pageHeader("Add to which shelf?", "A little flexibility");
              progressBar(25);
              echo '<form method="get">';
              foreach($shelves as $shelf) {
                echo '<div class="form-group"><label><input type="radio" name="shelf" value="'.$shelf->name.'">'.$shelf->name.'</label></div>';
              }
              echo '<input type="submit" class="btn btn-primary" value="Confirm Shelf">';
              echo '</form>';
              break;
            case 5:
              ?>
              <?php pageHeader("Amazon Wishlist We Should Look At?", "Put the public URL below"); ?>
              <?php progressBar(50); ?>
              <form id="main-form">
                <input type="hidden" name="shelf" value="<?php echo $_GET['shelf']; ?>">
                <div class="form-group"><label>Amazon Wishlist URL
                  <input type="text" name="wishlist" placeholder="http://amzn.com/w/1JI2IUH4RJRMW">
                </label></div>
                <input type="submit" class="btn btn-primary" value="Find Books">
              </form>
              <div class="panel panel-primary">
                <div class="panel-heading">Where?</div>
                <div class="panel-body">
                  <a href="http://www.amazon.com/gp/registry/wishlist/" target="_blank">Go to the wishlist on Amazon</a> and click "Share" in the top-right corner.
                </div>
              </div>
              <div class="panel panel-warning">
                <div class="panel-heading">This Might Take a Minute</div>
                <div class="panel-body">
                  Amazon doesn't have a wishlist API. This is going to scrape the URL and subsequent product URLs, which takes a while. You might start reading something else while you wait.
                </div>
              </div>
            <?php
              break;
            case 6:
              if(empty($_GET['wishlist'])) {
                pageHeader("You didn't put in a wishlist", "Use the back button in your browser to resolve this");
                break;
              }
              $wishlistUrl = $_GET['wishlist'];
              $regex = '/[\w]+\W+/i';
              $amazonID = preg_replace($regex, '', $wishlistUrl);
              try {
                $ch = curl_init('http://'.$_SERVER['HTTP_HOST'].'/vendor/doitlikejustin/amazon-wish-lister/src/wishlist.php?isbn=true&author=true&id='.$amazonID);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($ch);
                if (FALSE === $result)
                  throw new Exception(curl_error($ch), curl_errno($ch));
                $result = json_decode($result);
              } catch (Exception $e) {
                trigger_error(sprintf(
                  'Curl failed with error #%d: %s',
                  $e->getCode(), $e->getMessage()),
                  E_USER_ERROR);
              }
              ?>
              <?php pageHeader("So you want to add these books?", "These are the ones we found, anyway"); ?>
              <?php progressBar(75); ?>
              <div class="panel panel-warning">
                <div class="panel-heading">Give it a once-over</div>
                <div class="panel-body">
                  Sometimes albums and movies make it through as books with similar names, or studies on the book you want rank higher
                </div>
              </div>
              <form id="main-form">
                <input type="hidden" name="shelf" value="<?php echo $_GET['shelf']; ?>">
                <input type="hidden" name="wishlist" value="<?php echo $_GET['wishlist']; ?>">

                <?php
                $access_token = $_SESSION['access_token'];
                $obj = new GoodreadsOauth($key, $secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);
                $bookIds = [];
                // var_export($result);
                foreach($result as $wishlistItem) {
                  if(!empty($wishlistItem->isbn) && $wishlistItem->isbn != ':') {
                    $wishlistItem->isbn = str_replace('-', '', $wishlistItem->isbn);
                    $bestBookId = findGoodreadsMatch($wishlistItem, $obj->doGet('http://www.goodreads.com/search/index.xml?key='.$key.'&q='.$wishlistItem->isbn.'&search[field]=isbn'));
                    if($bestBookId !== false) {
                      $bookIds[$bestBookId] = $wishlistItem;
                    }
                  } else if($wishlistItem->author) {
                    $wishlistItem->name = cleanAmazonName($wishlistItem->name);
                    $bestBookId = findGoodreadsMatch($wishlistItem, $obj->doGet('http://www.goodreads.com/search/index.xml?key='.$key.'&q='.urlencode($wishlistItem->name.' '.$wishlistItem->author).'&search[field]=all'));
                    if($bestBookId !== false) {
                      $bookIds[$bestBookId] = $wishlistItem;
                    }
                  } else {
                    //assummed not a book
                  }
                }
                if(count($bookIds) != 0) {
                  ?>
                  <table class="table">
                    <tr>
                      <th>
                        Add
                      </th>
                      <th>
                        Thumbnail
                      </th>
                      <th>
                        Book
                      </th>
                    </tr>
                    <?php foreach($bookIds as $id => $wishlistItem) {
                      ?><tr>
                      <td>
                        <input type="checkbox" checked value="<?php echo $id; ?>" name="addBooks[]">
                      </td>
                      <td>
                        <img src="<?php echo $wishlistItem->picture; ?>">
                      </td>
                      <td>
                        <h3><?php echo $wishlistItem->name; ?></h3>
                        <small>
                          By <?php echo $wishlistItem->author; ?>
                        </small>
                      </td>
                    </tr>
                  <?php }
                }
                ?>
                </table>
              <input type="submit" class="btn btn-primary" value="Add To <?php echo ucwords($_GET['shelf']); ?> Shelf">
              </form>
            <?php
              break;
            case 7:
              $access_token = $_SESSION['access_token'];
              $obj = new GoodreadsOauth($key, $secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);
              $content = $obj->doPost('http://www.goodreads.com/shelf/add_books_to_shelves.xml', ['key'=> $key, 'bookids' => implode(',', $_GET['addBooks']), 'shelves' => $_GET['shelf']]);
              $result = json_decode(json_encode(simplexml_load_string($content)));
              if($result->result == 'ok') {
                pageHeader("Success!", "You can <a href=\"http://".$_SERVER['HTTP_HOST']."\">do it again</a> if you want.");
                progressBar(100);
              } else {
                pageHeader('Something went wrong...', '<a href="mailto:ethan.c.clevenger@gmail.com">Let me know</a>');
                progressBar(100, 'danger');
              }
            ?>
            <?php
              break;
          } ?>
        </div>
      </div>
    </div>
    <div class="footer">
      <p>Made with love by <a href="http://ethanclevenger91.github.io">Ethan Clevenger</a></p>
      <p>Something broken? <a href="mailto:ethan.c.clevenger@gmail.com">Email me</a></p>
    </div>
    <style>
      body {
        display:flex;
        flex-direction:column;
        min-height:100vh;
      }
      body > .container {
        flex:1;
      }
      #main-form {
        margin-bottom:2em;
      }
      .footer {
        text-align:center;
        background:#eeeeee;
        padding:2em 0;
      }
      table > tbody > tr > td {
        vertical-align:middle !important;
       }
      table h3 {
        margin:0;
      }
    </style>
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/scripts.js"></script> -->
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <!-- <script src="js/bootstrap.min.js"></script> -->
  </body>
</html>
