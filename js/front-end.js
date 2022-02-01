$('#searchButton').click( function() {
  queryData();
});

// $('#pagination').on( "click", 'li a', function() {
$('#pagination').on( "click", 'li', function() {
  queryData( $( this ) );
});

$('#results').on( "click", 'button', function() {
  // console.log($('.fullDescription'));
  $('.fullDescription').map( function(index, element) {
    // console.log(element);
    element.style.display = 'none';
  });
  console.log($( this ).siblings('.fullDescription'));
  const id = $( this ).siblings('.fullDescription').attr('id');
  const triggerElement = $('#' + id);
  console.log('ID is: ' + id);
  console.log(triggerElement);
  const table = $('#SearchType').val();
  console.log({action: 'getFullDescription', id: id, table: table});
  $.ajax({
    url: getPostScriptUrl()
    , method: 'post'
    , dataType: 'json'
    , data: {action: 'getFullDescription', id: id, table: table}
    , success: function(event) {
      // console.log('event');
      // console.log(event);
      // const results = event.results.map( function(result) {
        // console.log(result.Txt);
        triggerElement.html(event.results);
        triggerElement.show();
      // });
      // console.log('results');
      // console.log(results);
    }
    , error: function(response){
      // alert('server error occured')
      console.log('POST Response Error:');
      console.log(response);
    }
  });
});

function displayResults(allResults) {
  // console.log('displayResults input: ');
  // console.log(allResults);
  clearDeck();
  allResults.map( function(result) {
    // console.log('displayResults deck length: ' + getDeck().length);
    // buildHtmlForResult(result);
    addCard(result);
  });
  document.querySelector('#results').innerHTML = (
    getDeck().length === 0
    ? 'No Results Found. Please change the filter(s) and try again.'
    : getDeck().join('')
  );
  // console.log(getDeck());
}

function buildHtmlForResult(result) {
  const cardTemplateHtmlFile = 'card-template.html';
  const namePlaceholder = /@@NAME@@/i;
  const sourcePlaceholder = /@@SOURCE@@/i;
  const dbIdPlaceholder = /@@DB_ID@@/i;
  clearDeck();
  $.get( cardTemplateHtmlFile, function(data) {
    const html = data.replace(namePlaceholder, result.Name)
      .replace(sourcePlaceholder, result.Source)
      .replace(dbIdPlaceholder, result.ID);
    addCard('<div class="card-deck mb-3 text-center>' + html + '</div>');
  })
  .done( function() {
    // Set HTML content
    document.querySelector('#results').innerHTML = (
      getDeck().length === 0
      ? 'No Results Found. Please change the filter(s) and try again.'
      : getDeck().join('')
    );
  });
}

function getPostScriptUrl() {
  const currentUrl = window.location;
  return currentUrl .protocol + "//" + currentUrl.host + "/" + currentUrl.pathname.split('/')[1] + '/queryData.php';
}

function addCard(html) {
  const options = {
    action: 'add'
    , html: html
  };
  cardBuilder(options);
}

function getDeck() {
  const options = {
    action: 'get'
    , html: ''
  };
  return cardBuilder(options);
}

function clearDeck() {
  const options = {
    action: 'clear'
    , html: ''
  };
  cardBuilder(options);
}

function cardBuilder(options) {
  // console.log('Function cardBuilder: options');
  // console.log(options);
  if( typeof this.cards === 'undefined' || options.action.toString().toLowerCase() === 'clear' ) {
    this.cards = [];
  }
  if( options.action.toString().toLowerCase() === 'get' ) {
    return this.cards;
  }
  if( options.action.toString().toLowerCase() === 'add' ) {
    this.cards.push(options.html);
    return this.cards;
  }
  console.log('Function cardBuilder: No "action" options matched, so nothing will be done');
  return [];
}

function displayPagination(count, total, limit, start) {
  count = (
    start > 0
    ? Math.floor( start / limit ) * limit + count
    : count
  );
  const searchCountElement = $('#searchCount');
  const paginationElement = $('.pagination');
  searchCountElement.html('Showing <b>' + start + ' - ' + count + ' of ' + total + ' results');
  searchCountElement.show();
  if(total === 0 || isNaN(total)) {
    paginationElement.slideUp();
    return false;
  }
  console.log('Pagination HTML: ' + buildPageLinks(total, limit, start));
  if( start > 0 ) {
    const currentPageElement = $( '#' + Math.ceil( start / limit ) );
    $('.active').removeClass('active');
    currentPageElement.addClass('active');
  } else {
    paginationElement.html( buildPageLinks( total, limit, start ) );
    $('.page').first().addClass('active');
    paginationElement.slideDown();
  }
}

function buildPageLinks(total, limit, start) {
  if(start >= total) {
    return '';
  }
  console.log('Function buildPageLinks inputs:');
  console.log({total: total, limit: limit, start: start});
  // return '<li class="page-item"><a class="page-link page" value="' + start + '">'
  //   + Math.ceil(start / limit)
  //   + '</a></li>'
  //   + buildPageLinks(total, limit, start + limit);
  return '<li class="page-item page-link" id="' + start + '">'
    + Math.ceil(start / limit)
    + '</li>'
    + buildPageLinks(total, limit, start + limit);
}

function queryData(element) {
  element = element || null;
  const values = $( '#searchTerms' ).val().split(',');
  const table = $( '#SearchType' ).val();
  const limit = 100;
  const start = ( element === null ? 0 : Number( element.attr('id') ) );
  console.log('Value is: ' + ( element === null ? start : element.val()));
  console.log('Table: ' + table + ', start: ' + start + ', limit: ' + limit + ', and Values: ' + values + ', Script URL: ' + getPostScriptUrl());
  console.log({action: 'getResults', values: values, table: table, start: start, limit: limit});
  $.ajax({
    url: getPostScriptUrl()
    , method: 'post'
    , dataType: 'json'
    , data: {action: 'getResults', values: values, table: table, start: start, limit: limit}
    , success: function(event) {
      console.log('event');
      console.log(event);
      const results = event.results.map( function(result) {return result;});
      console.log('results');
      console.log(results);
      displayResults(results);
      displayPagination( results.length, Number( event.count ), limit, start );
    }
    , error: function(response){
      // alert('server error occured')
      console.log('POST Response Error:');
      console.log(response);
    }
  });
}
