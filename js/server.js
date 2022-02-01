// var express = require('express')
// const cors = require('cors');
// var app = express();
// var bodyparser = require('body-parser');
// var urlencodedparser = bodyparser.urlencoded({extended:false})
//
// app.set('views',__dirname + '/views');
// app.set('view engine', 'ejs');
// app.use(express.static(__dirname + '/public'));
// app.use(express.cookieParser());
// app.use(cors({
//     origin: '*'
// }));
//
// app.get('/', function (req, res){
//    res.render('index.html');
// });
//
// app.post('/ajax', urlencodedparser, function (req, res){
//    console.log(req);
//    console.log('req received');
//    res.send(JSON.stringify([
//      {
//        "Txt": "Testing1"
//      }
//      , {
//        "Txt": "Testing2"
//      }
//    ]));
//
// });
//
// app.listen(8888);
