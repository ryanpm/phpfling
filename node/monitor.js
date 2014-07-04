var chokidar = require('chokidar');
var fs = require('fs');
var watcher = chokidar.watch('../lib/', {ignored: /[\/\\]\./, persistent: true});



// watcher
//   .on('add', function(path) {console.log('File', path, 'has been added');})
//   .on('addDir', function(path) {console.log('Directory', path, 'has been added');})
//   .on('change', function(path) {console.log('File', path, 'has been changed');})
//   .on('unlink', function(path) {console.log('File', path, 'has been removed');})
//   .on('unlinkDir', function(path) {console.log('Directory', path, 'has been removed');})
//   .on('error', function(error) {console.error('Error happened', error);})

// 'add', 'addDir' and 'change' events also receive stat() results as second argument.
// http://nodejs.org/api/fs.html#fs_class_fs_stats
watcher
	.on('change', function(path, stats) {
		console.log('File', path, 'changed size to');
		console.log(stats);

		fs.readFile(path, 'utf8', function(err,data){
			if (err) {
				return console.log(err);
			}
			console.log(data);
		});

	});


// watcher.add('new-file');
// watcher.add(['new-file-2', 'new-file-3']);

// // Only needed if watching is persistent.
// watcher.close();

// // One-liner
// require('chokidar').watch('.', {ignored: /[\/\\]\./}).on('all', function(event, path) {
//   console.log(event, path);
// });
