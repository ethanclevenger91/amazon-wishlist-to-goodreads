<?php function findGoodreadsMatch(&$wishlistItem, $results) {
  $goodreadsBooks = simplexml_load_string($results);
  $goodreadsBooks = json_encode($goodreadsBooks);
  $goodreadsBooks = json_decode(str_replace('@', '', $goodreadsBooks));
  if($goodreadsBooks->search->{'total-results'} > 0) {
    if(is_array($goodreadsBooks->search->results->work)) {
      $goodreadsBooks->search->results->work = $goodreadsBooks->search->results->work[0];
    }
    $bestBook = $goodreadsBooks->search->results->work->best_book;
    $wishlistItem->author = $bestBook->author->name;
    $wishlistItem->name = $bestBook->title;
    $wishlistItem->picture = $bestBook->image_url;
    return $bestBook->id;
  } else {
    return false;
  }
}

function cleanAmazonName($name) {
  $name = preg_replace('/\([\ \w:]*Prize[\ \w:]*\)/', '', $name); //Amazon adds prize notation to book titles
  $name = str_replace(': A Novel', '', $name);
  return $name;
}

function pageHeader($title, $message) {
  echo '<div class="page-header">
    <h1>'.$title.'<br><small>'.$message.'</small></h1>
  </div>';
}

function progressBar($value, $class="success") {
  echo '<div class="progress">
    <div class="progress-bar progress-bar-'.$class.' progress-bar-striped" role="progressbar" aria-valuenow="'.$value.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$value.'%">
      <span class="sr-only">'.$value.'% Complete (success)</span>
    </div>
  </div>';
}
