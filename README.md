#### Access log Analyzer Class

##### How to use this class:

```shell
include_once('Classes/LogParser.php');
$parser = new Classes\LogParser();
$parser->setPath('example/access_log');
echo $parser->getJson();
```