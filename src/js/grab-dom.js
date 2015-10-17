var page = new WebPage();
var system = require('system');
var url = 'http://twitter.com/search/javascript';
var timeout = 8000;  


page.settings.resourceTimeout = 60000; // 60 seconds and we abort a request
page.onResourceTimeout = function(e) {
  console.log("PHANTOMJS: " + e.errorCode);  // it'll probably be 408 
  console.log("PHANTOMJS: " +e.errorString); // it'll probably be 'Network timeout on resource'
  console.log("PHANTOMJS: " + e.url);        // the url whose request timed out
  phantom.exit(1);
};

// error handler
phantom.onError = function(msg, trace) {
  var msgStack = ['PHANTOM ERROR: ' + msg];
  if (trace && trace.length) {
    msgStack.push('TRACE:');
    trace.forEach(function(t) {
      msgStack.push(' -> ' + (t.file || t.sourceURL) + ': ' + t.line + (t.function ? ' (in function ' + t.function +')' : ''));
    });
  }
  console.error(msgStack.join('\n'));
  phantom.exit(1);
};

function displayHelp () {
    console.log('Usage:');
    console.log(system.args[0] + ' \'http://twitter.com/search/javascript\'');
    phantom.exit(0);
}

function argParser () {
    if (system.args.length === 1) {        
        displayHelp();
        phantom.exit();          
    } else {
        //console.log(system.args.length);
        url = system.args[1];
        if (system.args[2] != 'undefined') {
            timeout = system.args[2];
        }       
    }
}

argParser();

try {
    page.open(url, function (status) {
        
        if (status == 'success') {
            // inject jquery
            //page.injectJs("http://code.jquery.com/jquery-latest.min.js", function() {
            // jQuery is loaded, now manipulate the DOM
            //});
            getFullDom();
        } else {
            console.log('failure open page');
            phantom.exit(1);
        }
    });

} catch (err) {
    console.log(err.message);
    phantom.exit(1);
}

function getFullDom() {
    window.setTimeout(function () {
        var results = page.evaluate(function() {
            return document.getElementsByTagName('html')[0].innerHTML;
            // If jquery is loaded you can use this: 
            //return $('html').html();
        });
        console.log(results);
        phantom.exit(0);
    }, timeout);
}
