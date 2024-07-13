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

