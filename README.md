# Userland Packages for PHP

**NOTE: This is still in alpha/development, so is not yet production ready**.

_Userland Packages for PHP_ provide PHP developers with **working** code to group a collection of `.php` files located in a single-directory into a **_new_** concept of _"Package,"_ with full control of _**file- and package-level visibility**_ and no _build-time requirements_.

## How to Use
Here is the simplest example I can envision:

### `./main.php`
```php
<?php
require "/path/to/autoload.php";
UserlandPackages::register();
require "phpkg://my-pkg";
echo hello(), ' ', world(), '!';
```
### `./my-pkg/hello.php`
```php
<?php
function hello():string {
   return 'Hello';
}
```
### `./my-pkg/world.php`
```php
<?php
function world():string {
   return 'World';
}
```

Running the above `main.php` of course prints out:

```
Hello World!
```

## `FileOnly` and `PackageOnly`

The following is the code from our demo showing how to load:

1. Two packages that **_both_** have _same-named_ classes `PackageOnly\A` and `PackageOnly\B`, and 
2. A package — `english-pkg` — where _**both**_ files `A.php` and `B.php` have a _same-named_ class `FileOnly\C`.

### `./main.php`
```php
<?php
// First register the use of Userland Packages
require "../src/autoload.php";
UserlandPackages::register();

// Next load your two packages
require "phpkg://english-pkg/";
require "phpkg://french-pkg/";

// First load English and call its greeting
$english = new English();
$english->greeting();

// Then load French and call its salutation
$french = new French();
$french->salutation();
```
Run the above and you'll see the result being:

```
World World
Bonjour le Monde
```

### `./english-pkg/English.php` && `./french-pkg/French.php`
Now compare the primary class file of each _package_ and notice they have almost idenitical code besides the one exported class for each:

- `English` vs.
- `French`.

#### `./english-pkg/English.php` 
```php
<?php
use PackageOnly\A;
use PackageOnly\B;
class English {
	public A $b;
	public B $c;
	public function __construct() {
		$this->b = new A();
		$this->c = new B();
	}
	public function greeting():void {
		$this->b->greeting();
		echo ' ';
		$this->c->greeting();
		echo "\n";
	}
}
```
#### `./french-pkg/French.php`
```php
<?php
use PackageOnly\A;
use PackageOnly\B;
class French {
	public A $b;
	public B $c;
	public function __construct() {
		$this->b = new A();
		$this->c = new B();
	}
	public function salutation():void {
		$this->b->salutation();
		echo ' ';
		$this->c->salutation();
		echo "\n";
	}
}
```
_Above there is also the **arbitrary** change we made to method names `greeting()` vs. `salutation()`. We did that do follow the spoken-language theme but not because we needed too. `greeting()` would have worked fine for both of them._ 

_In fact, we could have implemented a `Greatable` interface requiring a `greeting()` method had we wanted to._


### `./english-pkg/{A,B}.php`
Now let's look at the `A.php` and `B.php` file in the `english-pkg` package. Note how both define their own same-named and _non-conflicting_ class `C`. 

Note the only difference between the two (2) files is what each file's `FileOnly\C->greeting()` outputs:

#### `./english-pkg/A.php`
```php
<?php
namespace PackageOnly;
use FileOnly\C;
class A {
   public C $c;
   public function __construct() {
      $this->c = new C();
   }
   public function greeting() {
      $this->c->greeting();
   }
namespace FileOnly;
class C {
   public function greeting() {
      echo "Hello";
   }
}
```

#### `./english-pkg/B.php`
```php
<?php

namespace PackageOnly;
use FileOnly\C;
class B {
   public C $c;
   public function __construct() {
      $this->c = new C();
   }
   public function greeting() {
      $this->c->greeting();
   }
}

namespace FileOnly;
class C {
   public function greeting() {
      echo "World";
   }
}
```

### `./french-pkg/{A,B}.php`

Finally, we wrote the package `french-pkg` to be much simplier, just since we'd already shown all the necessary concepts. 

The one remaining concept is for you to verify that we have indeed implemented using same-named classes `PackageOnly\A` and `PackageOnly\B` when compared to `english-pkg`: 

### `./french-pkg/A.php`
```php
<?php
namespace PackageOnly {
	class A {
		public function salutation() {
			echo "Bonjour";
		}
	}
}
```
### `./french-pkg/B.php`
```php
<?php

namespace PackageOnly;
class B {
	public function salutation() {
		echo "le Monde";
	}
}
```


## W.R.T. PHP Namespaces
_Of important note:_ Userland Packages are **_(almost)_ _completely orthogonal_** to PHP namespaces. Userland Packages allow a PHP developer to use PHP Namespaces — _**or not**_ — and still be able to have  control of file- and package-level visibility that previously only namespaces provided. 

In fact, Userland Packages _uses_ namespaces to achieve its magic, but most of the time developers really do not really need to be cognizant the implementation details.


## PHP Versions supported
Userland Packages supports PHP `8.3x` _(and maybe earlier)_ and is itself a Composer-installable package utilizing PSR-4 namespaces. However "Packages" in Userland Packages are _(almost)_ _completely_ orthogonal to PHP namespaces.

## Raison d'etre
This repo exists to serve two (2) purposes. They are to:

1. Provide **working** PHP `8.3x` code that allows developers to create single-directory _"Packages"_ with full control of _**file- and package-level visibility**_, and
2. Serve as a Proof-of-Concept and model to convince voters who work with PHP RFCs to move forward to adopt single-level directory packages into PHP core.


## Goals and Non-Goals

## Goals
- Create a highly-function implementation of single-directory packages
- Enable file-level and package-level scoping
- Allow two or more classes, interfaces, enums and/or constants in a single .PHP file
- Use native-feeling loading mechanisms like `require()`
- Be completely orthogonal to namespaces _(with a tiny caveat, e.g. `FileOnly` and `PackageOnly` namespaces)_
- Fully-enable usage without a build process
- Enhance production performance with a build process
- Expose PHP developer to the beneficial of these packages
- And finally, **to engineer its own demise** and convince PHP RFC voters to add single-directory packages to PHP core

## Non-goals
- Coupling the concepts of packages to namespaces
- Growing the concepts of packages to allow for multiple directories


## PHP Versions supported
Userland Packages was developed using PHP `8.3.9` and thus supports PHP `8.3.x`, but probably also earlier `8.x` versions too. If you find features that are pre-`8.3.x` please let me know in the issues.

## Use-Cases

### Dependencies for WordPress Plugins
One of the most useful places to use Userland Packages with for when building plugins for WordPress. It is well known that if you ship a dependency with WordPress it is possible that some other plugin that uses a different version of the same plugin — or even the same version is not loaded with an autoloader — will conflict.

However, if the dependency is made available as a Userland Package than this concern can be made irrelevant.

It is true that — _currently_ – such dependencies would need to be written to be used as a Userland Package and that existing libraries without Userland Packages visibility scoping mechanisms can not be loaded without first modifying their source code. 

However, **as the goal of Userland Packages is to engineer its own demise** and convince those who vote on PHP RFCs that they should incorporate the concept of single-directory packages into PHP. 

Also, it is possible that Userland Packages will be able to solve the problem for pre-existing libraries if there is enough interest show in the discussion forums.

## FAQ

### Why only support single directories?
- Simplicity of Implementation
  - Simplicity translates into runtime performance
- Reduce Coupling and Enhance Cohesion:
  - The author believes:
    - If code doesn't fit into one directory, it really too big to be a single package.
    - The larger a package, the less cohesive the package becomes.
    - As a package grows the amount of damaging inter-package coupling increases.
