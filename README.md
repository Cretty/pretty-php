pretty-php
==========
----

###News: Pretty 4 Dev. preview 1 was finished. 

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

  public $foo = '@Foo'; // Yes, public! Just tell the name of class.
  
  public function doSomething() {
    $this->foo->doSomething();
  }
}
```
### Getting started with Pretty:
  Read demo codes. We're still working on riching the documents.
  
More features will be submit later.
