# TODO List
 
1. ~~Checksum for `.phpkg` packages~~
2. ~~`FormatInterface` interface~~
3. ~~Implement `FormatInterface` with `.phar`,`.phpkg`,`.zip`, and `.tar`~~  
4. ~~`.zip` packages~~
6. Alternate dir
7. APCu support
5. `.tar` packages
8. PHPUnit Test Harness
9. Support loading same-named symbols not in Packages
10. CLI tool to build packages for CI/CD
11. Add logic to test if .PHAR can be generated 
    - `phar.readonly` must be `0` in `php.ini`
12. Lockfile `.phplk` for `.phpkg` packages
13. Require package names to be `A-Z0-9_` only
14. Lazy-loading with a classmap
    -. Scanning file for tokens needs to build map of symbols name/filepaths