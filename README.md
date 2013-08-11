pretty-php
==========
----

###News: Pretty 4 Dev. preview 2 was finished.

#### What's Pretty?
  Pretty is a tiny framework written in PHP.

#### What will Pretty do?
  Pretty provides a very simple way to organize the dependences between the codes.

  Let's see an example:

```PHP
# Notice: The sample codes below only work with Pretty 4.
# Codes without Pretty
$username = null;
if isset($_POST['username']) {
    $username = $_POST['username'];
}
$output = json_encode(array('username' => $username));
header('content-type: application/json');
echo $output;

# Now Let's do it with Pretty (In pretty Action):
$username = $this->getPost('username', null);
$this->put('username', $username);
$this->setView('json');

# Class without Pretty
class A {

    private $foo;
  
    public function __construct() {
        require_once('foo.class.php');
        $this->foo = new Foo();
    }
  
    public function doSomething() {
        $this->foo->doSomething();
    }
}

# Class with Pretty
class A {

    public $foo = '@Foo'; // Yes, public! Just telling the name of class.
  
    public function doSomething() {
        $this->foo->doSomething();
    }
}
```
Making an url request just like:
```php
# Let's make it more pretty!
class Index extends Action {

    public $helper = '@.helper.IndexHelper';
    public $dbLink0 = '@+.mysql.MysqlAdapter';
    public $dbLink1 = '@+.mysql.MysqlAdapter';
    public $foo = '@*foo';
    
    protected function run() {
        $a = $this->helper->help($this->get('key', 'defaults'));
        $b = $this->dbLink0->query('SELECT 1');
        $c = $this->dbLink1->query('SELECT 2');
        $d = $this->foo->doIt();
        $this->put(
            array(
                'a' => $a,
                'b' => $b,
                'c' => $c,
                'd' => $d
            )
        );
        if ($this->get('format') == 'json') {
            $this->setView('json');
        } else {
            $this->setView('smarty', 'index');
        }
    }
}
```
### Getting started with Pretty:
  Read demo codes. We're still working on riching the documents.

More features will be submit later.
