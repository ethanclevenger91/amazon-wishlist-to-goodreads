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
    <meta property="og:title" content="Fill My Shelves" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo $_SERVER['HTTP_HOST']; ?>" />

    <meta property="og:site_name" content="Fill My Shelves" />
    <meta property="og:description" content="Port books on your Amazon wishlist to your Goodreads account" />
    <title>Fill My Shelves</title>

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
    // error_reporting(0);
    use OAuth\OAuth1\Service\Goodreads;
    use OAuth\Common\Storage\Session;
    use OAuth\Common\Http\Exception\TokenResponseException;
    use OAuth\Common\Consumer\Credentials;
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__.'/config.php';
    require_once __DIR__.'/includes/functions.php';
    $uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
    $currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
    $currentUri->setQuery('');
    $serviceFactory = new \OAuth\ServiceFactory();
    $storage = new Session();

    // Setup the credentials for the requests
    $credentials = new Credentials(
      GOODREADS_KEY,
      GOODREADS_SECRET,
      $currentUri->getAbsoluteUri()
    );
    $goodreadsService = $serviceFactory->createService('Goodreads', $credentials, $storage);

    if($storage->hasAccessToken('Goodreads')) {
      try {
        $checkValid = $goodreadsService->request('api/auth_user');
        $case = 2;
      } catch(TokenResponseException $e) {
        //do nothing
      }
    }
    if(!isset($case) && !empty($_GET['oauth_token'])) {
      $token = $storage->retrieveAccessToken('Goodreads');
      // This was a callback request from goodreads, get the token
      $goodreadsService->requestAccessToken(
          $_GET['oauth_token'],
          '', //goodreads is not currently passing back an oauth_verifier, see here: https://www.goodreads.com/topic/show/2043791-missing-oauth-verifier-parameter-on-user-auth-redirect
          $token->getRequestTokenSecret()
      );
      $case = 2;
    } else if (!empty($_GET['go']) && $_GET['go'] === 'go') {
      // extra request needed for oauth1 to request a request token :-)
      $token = $goodreadsService->requestRequestToken();
      $url = $goodreadsService->getAuthorizationUri(array('oauth_token' => $token->getRequestToken(), 'oauth_callback' => GOODREADS_CALLBACK));
      header('Location: ' . $url);
    } else if(!isset($case)) {
      // $storage->clearAllTokens();
      $case = 1;
    } ?>
    <div class="container">
      <div class="row">
        <div class="col-xs-12 col-md-8 col-md-offset-2">
          <?php if(isset($_GET['shelf']) && !isset($_GET['wishlist']) && !isset($_GET['addBooks'])) { //shelf picked, get wishlist
            $case = 3;
          } else if(isset($_GET['shelf']) && isset($_GET['wishlist']) && !isset($_GET['addBooks'])) { //wishlist picked, get books
            $case = 4;
          } else if(isset($_GET['shelf']) && isset($_GET['wishlist']) && isset($_GET['addBooks'])) { //add the books!
            $case = 5;
          }
          switch($case) {
            case 1: //no request token or access token, get request token and sent user to authorize
              $authorize_url = $currentUri->getRelativeUri() . '?go=go';

              pageHeader("We'd Start, But...", "You haven't authorized access to your Goodreads"); ?>
              <?php progressBar(0); ?>
              <div style="text-align:center">
                <a class="btn btn-primary" href="<?php echo $authorize_url; ?>">Click Here to Access Goodreads</a>
              </div>
              <?php break;
            case 2: // all's well, let's pick a shelf

              $user_obj = xml_to_json($goodreadsService->request('api/auth_user'));
              $user = $user_obj->GoodreadsResponse->user;
              $shelves = xml_to_json($goodreadsService->request('shelf/list.xml?key='.GOODREADS_KEY.'&user_id='.$user->{'@id'}.'&page=1'))->GoodreadsResponse->shelves->user_shelf;
              pageHeader("Add to which shelf?", "A little flexibility");
              progressBar(25);
              echo '<form method="get">';
              foreach($shelves as $shelf) {
                echo '<div class="form-group"><label><input type="radio" name="shelf" value="'.$shelf->name.'">'.$shelf->name.'</label></div>';
              }
              echo '<input type="submit" class="btn btn-primary" value="Confirm Shelf">';
              echo '</form>';
              break;
            case 3:
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
            case 4:
              if(empty($_GET['wishlist'])) {
                pageHeader("You didn't put in a wishlist", "Use the back button in your browser to resolve this");
                break;
              }
              $wishlistUrl = $_GET['wishlist'];
              $regex = '/[\w]+\W+/i';
              $amazonID = preg_replace($regex, '', $wishlistUrl);
              try {
                $ch = curl_init('http://'.$_SERVER['HTTP_HOST'].'/vendor/ethanclevenger91/amazon-wish-lister/src/wishlist.php?isbn=true&author=true&id='.$amazonID);
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
                $bookIds = [];
                // var_export($result);
                foreach($result as $wishlistItem) {
                  if(!empty($wishlistItem->isbn) && $wishlistItem->isbn != ':') {
                    $wishlistItem->isbn = str_replace('-', '', $wishlistItem->isbn);
                    $books = xml_to_json($goodreadsService->request('search/index.xml?key='.GOODREADS_KEY.'&q='.$wishlistItem->isbn.'&search[field]=isbn'));
                    $bestBookId = findGoodreadsMatch($wishlistItem, $books);
                    if($bestBookId !== false) {
                      $bookIds[$bestBookId] = $wishlistItem;
                    }
                  } else if($wishlistItem->author) {
                    $wishlistItem->name = cleanAmazonName($wishlistItem->name);
                    $books = xml_to_json($goodreadsService->request('search/index.xml?key='.GOODREADS_KEY.'&q='.urlencode($wishlistItem->name.' '.$wishlistItem->author).'&search[field]=all'));
                    $bestBookId = findGoodreadsMatch($wishlistItem, $books);
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
            case 5:
              $result = xml_to_json($goodreadsService->request('shelf/add_books_to_shelves.xml', 'POST', ['key' => GOODREADS_KEY, 'bookids' => implode(',', $_GET['addBooks']), 'shelves' => $_GET['shelf']]));
              if($result->GoodreadsResponse->result == 'ok') {
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
        font-family:"Trebuchet MS", sans-serif;
        font-size:17px;
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
      h1 {
        font-family:Georgia;
        line-height:1;
      }
      small {
        display:block;
        font-family:"Trebuchet MS", sans-serif;
        font-weight:300 !important;
        margin-top:.3em;
        line-height:1.2 !important;
      }
      input[type="radio"] {
        margin-top:9px;
        margin-right:9px;
      }
    </style>
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/scripts.js"></script> -->
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <!-- <script src="js/bootstrap.min.js"></script> -->
  </body>
</html>
