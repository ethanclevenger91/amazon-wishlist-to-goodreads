//Single-page app was a nice idea, but ultimately not gonna happen.
//OAuth in single-page app is dangerous and Goodreads doesn't have CORS support
// jQuery(document).ready(function($) {
//   // $('#main-form').submit(function(e) {
//   //   e.preventDefault();
//   //   var wishlistUrl = $('input[name="wishlist"]').val();
//   //   if(!wishlistUrl) {
//   //     wishlistUrl = 'http://amzn.com/w/3MM4LW0VB9V4A';
//   //   }
//   //   var regex = /[\w]+\W/ig;
//   // 	var amazonID = wishlistUrl.replace(regex,"");
//   //   $.post('/vendor/doitlikejustin/amazon-wish-lister/src/wishlist.php?isbn=true&id='+amazonID, function(data) {
//   //     $('#main-results').html(JSON.stringify(data, null, 2));
//   //     $.each(data, function(i, obj) {
//   //       if(obj.isbn) {
//   //         $.get('https://www.googleapis.com/books/v1/volumes?q='+obj.name, function(result) {
//   //           // console.log(result.items[0]);
//   //           $.get('https://www.goodreads.com/book/isbn_to_id?key='+goodreadsKey+'&isbn='+result.items[0].volumeInfo.industryIdentifiers[0].identifier, function(result) {
//   //             $obj = $.parseXML(result);
//   //             console.log($obj);
//   //             $('#main-result').append($obj.find('title'));
//   //           });
//   //         })
//   //       }
//   //     });
//   //   });
//   //
//   // })
// })
//
// // Changes XML to JSON
// function xmlToJson(xml) {
//
// 	// Create the return object
// 	var obj = {};
//
// 	if (xml.nodeType == 1) { // element
// 		// do attributes
// 		if (xml.attributes.length > 0) {
// 		obj["@attributes"] = {};
// 			for (var j = 0; j < xml.attributes.length; j++) {
// 				var attribute = xml.attributes.item(j);
// 				obj["@attributes"][attribute.nodeName] = attribute.nodeValue;
// 			}
// 		}
// 	} else if (xml.nodeType == 3) { // text
// 		obj = xml.nodeValue;
// 	}
//
// 	// do children
// 	if (xml.hasChildNodes()) {
// 		for(var i = 0; i < xml.childNodes.length; i++) {
// 			var item = xml.childNodes.item(i);
// 			var nodeName = item.nodeName;
// 			if (typeof(obj[nodeName]) == "undefined") {
// 				obj[nodeName] = xmlToJson(item);
// 			} else {
// 				if (typeof(obj[nodeName].push) == "undefined") {
// 					var old = obj[nodeName];
// 					obj[nodeName] = [];
// 					obj[nodeName].push(old);
// 				}
// 				obj[nodeName].push(xmlToJson(item));
// 			}
// 		}
// 	}
// 	return obj;
// };
