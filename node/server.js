var http = require("http");
var url  = require("url");
var counter = 0;

exports.start = function(){
	http.createServer(function(request, response) {

	var url_parsed = url.parse(request.url);

	var pathname = url_parsed.pathname;
	var query = url_parsed.query;


	console.log( url.parse(request.url) )
	console.log( query.split('&') );

	console.log( url.parse(request.url) )
	response.writeHead(200, {"Content-Type": "text/plain"});
	response.write("Hello World" +pathname+' '+counter);
	// response.write(counter);
	counter++;
	response.end();
	}).listen(8888);
}