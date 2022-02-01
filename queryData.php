<?php
try {
  // $_POST = [
  //   // "action" => "getFullDescription"
  //   // , "id" => "2389"
  //   // , "table" => "Feat"
  //   // 'table' => 'Class'
  //   'table' => 'Power'
  //   // // , 'action' => 'getFullDescription'
  //   , 'action' => ''
  //   // , 'id' => '2'
  //   , 'values' => [
  //     'ogue'
  //   ]
  //   , 'start' => 0
  //   , 'limit' => 100
  //   // , 'filters' => [
  //   //   (object) [
  //   //     'name' => 'Txt'
  //   //     , 'value' => 'Rogue'
  //   //   ]
  //   // ]
  // ];
  if ( empty( $_POST ) ) {
    echo "<p>This page can only be called via a POST request.</p>";
  }

  $results = query();
  // echo 'Count of Results: ' . count($results) . PHP_EOL;
  // echo var_dump($results);
  $htmlResults = buildHtmlResults($results);
  // echo 'Results for ' . $_POST['table'] . PHP_EOL;
  // echo var_dump($htmlResults);

  // Return the results
  echo json_encode((object)[
    "postData" => $_POST
    , "queryString" => buildQuery()
    , "results" => $htmlResults
    , "count" => getCount()
  ]);
} catch (\Exception $e) {
  echo 'Exception thrown: ' . $e->getMessage();
}

/**
 * Function used to create and execute the SQL query based off of the POST request
 * @return {{}[]} - This function will return an array of objects containing the
 * search results based on the query constructed from the POST request. If no
 * results were found, then an empty array will be returned
 */
function query() {
  $results = [];
  $queryString = buildQuery();
  $conn = buildConnection();
  foreach ( ( $conn->query( $queryString ) ) as $row ) {
    array_push($results, $row);
  }
  $conn = null;
  return $results;
}

function buildQuery() {
  if( $_POST['action'] == 'getFullDescription' ) {
    return 'SELECT Txt FROM ' . $_POST['table'] . ' WHERE ID = ' . $_POST['id'];
  }
  switch( strtoupper( $_POST['table'] ) ) {
    case 'CLASS':
      return 'SELECT ID, Name, Role, Txt FROM ' . $_POST['table'] . ' WHERE '
        . buildWhereClause($_POST['values']) . ' ORDER BY Name '
        . 'LIMIT ' . $_POST['start'] . ', ' . $_POST['limit'];
    case 'POWER':
      return 'SELECT ID, Name, Level, Action, Class, Source, Kind, Power.Usage, Txt FROM ' . $_POST['table']
        . ' WHERE ' . buildWhereClause($_POST['values']) . ' ORDER BY Level ASC, Name ASC, Kind ASC LIMIT '
        . $_POST['start'] . ', ' . $_POST['limit'];
    case 'FEAT':
      return 'SELECT ID, Name, Source, Tier, Txt FROM ' . $_POST['table']
        . ' WHERE ' . buildWhereClause($_POST['values']) . ' ORDER BY FIELD(Tier, "Heroic", "Paragon", "Epic") LIMIT '
        . $_POST['start'] . ', ' . $_POST['limit'];
    case 'RACE':
      return 'SELECT ID, Name, Size, Description, Source, Txt FROM ' . $_POST['table']
        . ' WHERE ' . buildWhereClause($_POST['values']) . ' ORDER BY Name LIMIT '
        . $_POST['start'] . ', ' . $_POST['limit'];
    case 'ITEM':
      return 'SELECT ID, Name, Cost, Level, Category, Enhancement, Source, Txt FROM '
        . $_POST['table'] . ' WHERE ' . buildWhereClause($_POST['values'])
        . ' ORDER BY CAST(Level as UNSIGNED), Name LIMIT ' . $_POST['start'] . ', ' . $_POST['limit'];
    default:
      return 'SELECT ID, Name, Txt FROM ' . $_POST['table'] . ' WHERE '
        . buildWhereClause($_POST['values']) . ' ORDER BY Name LIMIT '
        . $_POST['start'] . ', ' . $_POST['limit'];
  }
}

function buildHtmlResults($results) {
  // echo 'Count of buildHtmlResults Results: ' . count($results) . PHP_EOL;
  if( $_POST['action'] == 'getFullDescription' ) {
    return $results[0][0];
  }
  switch( strtoupper( $_POST['table'] ) ) {
    case 'CLASS':
      return buildHtmlResultClass($results);
    case 'POWER':
      return buildHtmlResultPower($results);
    case 'FEAT':
      return buildHtmlResultFeat($results);
    case 'RACE':
      return buildHtmlResultRace($results);
    case 'ITEM':
      return buildHtmlResultItem($results);
    default:
      return $results;
  }
}

function buildWhereClause($filters) {
  $whereClauses = array_reduce($filters, function($carry, $filter) {
    array_push($carry, 'Txt LIKE "%' . $filter . '%"');
    return $carry;
  }, ['Txt NOT LIKE "%Dragon Magazine%"', 'Txt NOT LIKE "%Dungeon Magazine%"']);
  return join( ' AND ', $whereClauses );
}

function getCount() {
  if(array_key_exists('values', $_POST) === false) {
    return 0;
  }
  $query = 'SELECT Count(*) as "count" FROM ' . $_POST['table'] . ' WHERE '
    . buildWhereClause($_POST['values']);
  $conn = buildConnection();
  $result = $conn->query($query)->fetchAll();
  return $result[0][0];
}

function buildConnection() {
  $user = 'ben';
  $pass = 'Ben123';
  return new PDO('mysql:host=localhost;dbname=dnd4e', $user, $pass);
}

function getCardHtmlTemplate() {
  $cardHtmlTemlplateFile = 'card-template.html';
  return file_get_contents($cardHtmlTemlplateFile);
}

function buildHtmlResultClass($results) {
  // echo 'Count of HtmlResultClass Results: ' . count($results) . PHP_EOL;
  return array_map( function($result) {
    return str_replace(
      '@@FEATURE_DESCRIPTION@@'
      , $result['Name']
      , str_replace(
        '@@TITLE@@'
        , $result['Role']
        , str_replace(
          '@@DB_ID@@'
          , $result['ID']
          , getCardHtmlTemplate()
        )
      )
    );
  }, $results);
}
// Name, Level, Action, Class, Source
function buildHtmlResultPower($results) {
  return array_map( function($result) {
    return str_replace(
      '@@FEATURE_DESCRIPTION@@'
      , '<ul>'
        . '<li>Class: <i>' . $result['Class'] . '</i></li>'
        . '<li>Level: <i>' . $result['Level'] . '</i></li>'
        . '<li>Source: <i>' . $result['Source'] . '</i></li>'
        . '<li>Action Category: <i>' . $result['Kind'] . '</i></li>'
        . '<li>Action Type: <i>' . $result['Usage'] . '</i></li>'
        . '</ul>'
      , str_replace(
        '@@TITLE@@'
        , $result['Name']
        , str_replace(
          '@@DB_ID@@'
          , $result['ID']
          , getCardHtmlTemplate()
        )
      )
    );
  }, $results);
}

function buildHtmlResultFeat($results) {
  return array_map( function($result) {
      return str_replace(
        '@@FEATURE_DESCRIPTION@@'
        , '<ul>'
          . '<li>Tier: <i>' . $result['Tier'] . '</i></li>'
          . '<li>Source: <i>' . $result['Source'] . '</i></li>'
          . '</ul>'
        , str_replace(
          '@@TITLE@@'
          , $result['Name']
          , str_replace(
            '@@DB_ID@@'
            , $result['ID']
            , getCardHtmlTemplate()
          )
        )
      );
    }, $results);
}

function buildHtmlResultRace($results) {
  return array_map( function($result) {
    return str_replace(
      '@@FEATURE_DESCRIPTION@@'
      , '<ul>'
        . '<li>Size: <i>' . $result['Size'] . '</i></li>'
        . '<li>Description: <i>' . $result['Description'] . '</i></li>'
        . '<li>Source: <i>' . $result['Source'] . '</i></li>'
        . '</ul>'
      , str_replace(
        '@@TITLE@@'
        , $result['Name']
        , str_replace(
          '@@DB_ID@@'
          , $result['ID']
          , getCardHtmlTemplate()
        )
      )
    );
  }, $results);
}

function buildHtmlResultItem($results) {
    return array_map( function($result) {
      return str_replace(
        '@@FEATURE_DESCRIPTION@@'
        , '<ul>'
          . '<li>Category: <i>' . $result['Category'] . '</i></li>'
          . '<li>Level: <i>' . $result['Level'] . '</i></li>'
          . '<li>Enhancement: <i>' . $result['Enhancement'] . '</i></li>'
          . '<li>Source: <i>' . $result['Source'] . '</i></li>'
          . '</ul>'
        , str_replace(
          '@@TITLE@@'
          , $result['Name']
          , str_replace(
            '@@DB_ID@@'
            , $result['ID']
            , getCardHtmlTemplate()
          )
        )
      );
    }, $results);
  return array_map( function($result) {}, $results);
}

?>
