# What If (for PHP)?

1. Single-directory packages
2. File-scoped visibility for classes/interfaces/enums/functions _and_ variables?
3. Anonymous namespaces
4. `include*()` and `require()` support:
   - support for a stream context parameter
   - accepts an array of `\PhpToken`s
5. Convert an `int` into a `resource`
6. Limit parameter for:                   
   - `get_declared_classes()`
   - `get_declared_interfaces()`
   - `get_declared_traits()`
7. Allow `stream_filter_append()` to filter `include*()` and `require()`
8. Classes could extend based on an instance of another class
   - Allow hidden code to export the ability to subclass
   - without having to worry about same-named class conflicts
   ```php
   $base = new Base();
   class Foo extends $base {}
   ```
9. OR classes could extend based on an instance of a Prototype object, and classes could implement based on a prototype
   ```php
   $myClass = new \PHP\Prototype('MyClass')
   $myIFace = new \PHP\Prototype('MyInterface')
   class Foo extends $myClass implements $myIFace {} 
   ```
   