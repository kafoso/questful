Questful
================================

Questful is an interfacing and metadata tool, providing a sensible link between HTTP queries and RESTful APIs. The name "Questful" is a word play and concatenation of the words "Query" (from HTTP query) and "RESTful".

---

# Introduction

RESTful APIs are widely used throughout the web. While a consensus has emerged about how to structure HTTP requests and responses, disparity and disagreement still exists when it comes to filtering and sorting data for multiple results (lists).

Questful aims to close part of this gap by providing strict, manageable, and secure means of applying filters and sorting, all the way from HTTP requests through the extraction of data from a storage unit<sup>[1](#footnotes-storage_unit)</sup> (e.g. database), and providing data and metadata in the final HTTP response.

## Purpose

The primary goal of Questful is to allow developers to quickly manage and implement filtering and sorting options in web applications, so that they may focus their energy and skill on making great applications, instead of repeatedly implementing trivial procedures.

## A quick example

In Questful, finding all users called "Homer" and then sorting (alphanumerically) by their names is as easy as:

```
GET /user?filter[]=name=%"Homer"%&sort[]=name
```

PHP code for capturing and processing the above request:

```php
<?php
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Bridge\PdoMySql\PdoMySql5_5;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;

$queryParserFactory = new QueryParserFactory;
try {
    $queryParser = $queryParserFactory->createFromUri($_SERVER['REQUEST_URI']);
    $queryParser->parse(); // Captures malformed expressions; throws exceptions
    $mapping = new Mapping($queryParser);
    $mapping
        ->relate('name', 'u.name') // Allow this relation
        ->allow(new Allowable\Filter\AllowedLikeFilter('name')) // Allow a LIKE filter match
        ->allow(new Allowable\AllowedSort('name')) // Allow this sorting match
        ->validate(); // Validates input values (queryParser) vs allowed; throws exceptions
    $pdoMySql = new PdoMySql5_5($mapping);
    $pdoMySql->generate(); // Generates SQL and parameters; throws exceptions

    $pdo = \PDO::getInstance(); // Some fully configured PDO instance
    $stmt = $pdo->prepare(
        "SELECT u.*
        FROM User u
        WHERE {$pdoMySql->getWhere()}
        ORDER BY {$pdoMySql->getOrderBy()};"
    );
    $stmt->execute($pdoMySql->getParameters());
    $json = json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
    header("HTTP/1.0 200 OK");
    echo $json;
} catch (BadRequestException $e) {
    header("HTTP/1.0 400 Bad Request");
    throw $e;
} catch (\Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    throw $e;
}
```

<a name="filtering"></a>
# Filtering

Filters (or conditions) allow the extraction of subsets of data from a storage unit.

## `?filter[]`

In Questful, filters must be arrays and thus, an HTTP query must be specified as such: `?filter[]=foo="bar"`. This filter has the index of `0` (zero) and is equivalent to: `?filter[0]=foo="bar"`. Indexes may be chosen freely, but must be integers and unique. Non-unique indexes will result in only the lastly defined filter (with a non-unique index) being applied.

Indexes are used in conjunction with [filter expressions](#filtering-filter_expression), which is explained futher down.

If a non-integer (e.g. `filter[a]=foo="bar"`) or negative integer (e.g. `filter[-1]=foo="bar"`) is provided as an index a [`Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php) is thrown.

Notice: The name "filter" is **singular**.

> **Why is there an equal sign, `=`, after `foo`?**

That's because `foo` is the **key** and `"bar"` is the **value**; i.e. a "key-value" pair. Everything past the first equal sign should be URL encoded before being sent to the API, making it look like `?filter[]=foo%3D%22bar%22`. That's not very pretty - or readable - so for simplicity's sake we stick to unencoded strings in most examples.

The equal sign after `foo` is just [one of several operators](#filtering-filter-operators); details futher down.

> **Why is `"bar"` wrapped in double quotes?**

That's because we specifically want to [target the data type "string"](#filtering-filter-filter_types).

### Syntax

```
?filter[]=<key><operator><value>
```

, where:

- `<key>` is a mapped key. More on [mapping](#mapping_and_validation) further down.
- `<operator>` is one of a variety of [operators](#filtering-filter-operators).
- `<value>` is one of a variety of [filter types](#filtering-filter-filter_types).

<a name="filtering-filter-filter_types"></a>
### Filter types

Questful handles the following:

- Null (`null`)
- Scalar values, i.e.:
  - `boolean` (`true` or `false`)
  - `float`
  - `integer`
  - `string`
- Substrings (`LIKE`)
- Arrays (`IN`)

**Notice:** Use `LIKE` with **extreme care**. This search type is tied to a certain risk of Denial of Service attacks<sup>[2](#footnotes-denial_of_service_attacks)</sup>. **Only allow** this search type if you are absolutely sure you know what your are doing. It may come in handy if providing a rich search feature to a restricted number of users (e.g. super users or administrators).

As a security measure, all filters and sorting must be [mapped](#mapping_and_validation).

Filter values must evaluate to PHP syntax (excluding modifiers).

<a name="filtering-filter-filter_types-null"></a>
#### Null

Must be left empty or spelled out as `null`.

##### Accepted operators

- `=`
- `!=`

##### Samples

- `?filter[]=foo=` (empty)
- `?filter[]=foo=null`

<a name="filtering-filter-filter_types-booleans"></a>
#### Booleans

Must be spelled out as "true" or "false", and **not** `1` or `0`; these characters are by Questful considered to be [integers](#filtering-filter-filter_types-integers).

##### Accepted operators

- `=`

##### Samples

- `?filter[]=foo=true`
- `?filter[]=foo=false`

<a name="filtering-filter-filter_types-floats"></a>
#### Floats

The decimal point period, `.`, and a precision of at least 1 decimal number is mandatory. An optional minus symbol may be provided. Simply specifying `1` will cause Questful to interpret the value as an [integer](#filtering-filter-filter_types-integers).

##### Accepted operators

- `=`
- `!=`
- `<`
- `<=`
- `>`
- `>=`

##### Samples:

- `?filter[]=foo=1.0`
- `?filter[]=foo<3.14`
- `?filter[]=foo=-273.15`

<a name="filtering-filter-filter_types-integers"></a>
#### Integers

Digits. An optional minus symbol may be prepended.

##### Accepted operators

- `=`
- `!=`
- `<`
- `<=`
- `>`
- `>=`

##### Samples:

- `?filter[]=foo=1`
- `?filter[]=foo=42`
- `?filter[]=foo=-512`

<a name="filtering-filter-filter_types-strings"></a>
#### Strings

Strings must be wrapped in double quotes `""`. The double quotes make strings clearly distinguishable from other data types, e.g. when an integer is in fact a string like `"42"`.

The value part, i.e. the double quotes and the contents inside it, must evaluate to PHP syntax. Naturally, encapsed variables (e.g `"$a"`) are not supported and will be considered to be part of the string. Double quotes inside the string must be escaped by backslash, e.g. `"\""`.

Single quotes are **not supported** and will cause a [`\Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php) to be thrown.

##### Accepted operators

- `=`
- `!=`
- `<`
- `<=`
- `>`
- `>=`

##### Modifiers

- `/i`<br>
Makes searching case insensitive.

##### Samples

- `?filter[]=foo="bar"`
- `?filter[]=foo="42"`
- `?filter[]=foo="bar"/i`
- `?filter[]=foo="foo \"bar\""`

<a name="filtering-filter-filter_types-substrings_like"></a>
#### Substrings (`LIKE`)

The terminology `LIKE` is borrowed from SQL and it works the same: finds substrings within other strings, commonly in columns in an SQL database table.

Same rule applies as for [strings](#filtering-filter-filter_types-strings), i.e. that the value part must evaluate to PHP syntax and that **only double quotes are supported**.

The syntax is a little different from SQL, though. While the percentage symbol `%` is used in both Questful and SQL, and may be used as a wildcard on the lefthand side, righthand side, or both at once, the symbol goes outside the quotes in Questful. I.e. `%"bar"%`, and **not** `"%bar%"`, as the syntax would be in SQL.

> **But why isn't the syntax the same?**

Because we want to reliably perform wildcard searching without the necessity of - and reliance on - proper escaping in the client.

**Example:**

Consider the following table:

| key | percentage |
| --- | ---------- |
| a   | 10%        |
| b   | 100%       |

It is correct that "10%" in the column "percentage" will be matched by this SQL statement:

```sql
SELECT * FROM table WHERE percentage LIKE "10%";
```

But so will "100%", because `%` is a wildcard character, matching `0%` within the "100%". That's not what we want. Therefore, percentage symbols go **outside the quotes** in Questful. This makes the above query become:

```sql
SELECT * FROM table WHERE percentage LIKE "10\%%" ESCAPE '\\';
```

Questful will ensure that both symbols `%` and `_` are escaped in MySQL.

##### Accepted operators

- `=`
- `!=`

##### Modifiers

- `/i`<br>
Makes search case insensitive.

Modifiers are appended **after** the last double quote or percentage symbol.

##### Samples

- `?filter[]=foo=%"bar"`
- `?filter[]=foo="bar"%`
- `?filter[]=foo=%"bar"%`
- `?filter[]=foo="100%"%`
- `?filter[]=foo="bar"%/i`

<a name="filtering-filter-filter_types-arrays_in"></a>
#### Arrays (`IN`)

The terminology `IN` is - as for `LIKE` - borrowed from SQL. It enables a condition to look for multiple values within the same key (or column).

An array search is a more manageable alternative to writing a series of `or`-statements (see [Filter expression](#filtering-filter_expression) further down).

Array filters are wrapped in square brackets, `[]` - and not round parentheses (as is the case in MySQL) - and values must be comma separated.

The contents of the square brackets must evaluate to PHP syntax. Otherwise, a [`\Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php) is thrown.

##### Syntax

```
?filter[]=<key><operator>[<value_1>, <value_2>, ... , <value_n>]/<modifiers>
```

, where:

- `<key>` is a mapped key.
- `<operator>` is an [accepted operator](#filtering-filter-filter_types-arrays_in-accepted_operators).
- `<value_1>, <value_2>, ... , <value_n>` are values of supported and mapped data types.
- `<modifiers>` is optional and takes only the modifier `i`, making the match case insensitive for strings.

<a name="filtering-filter-filter_types-arrays_in-accepted_operators"></a>
##### Accepted operators

- `=`
- `!=`

##### Supported data types

- **Null**<br>
Must be spelled out as "null". E.g. `[null]`.
- **Boolean**<br>
Must be spelled out as "true" or "false". E.g. `[true]` or `[false]`.
- **Float**<br>
May contain an optional minus symbol at the beginning, and must then consist of only digits and a single period. A precision of at least one decimal is mandatory. E.g. `[1.0]` or `[3.14]`.
- **Integer**<br>
May contain an optional minus symbol at the beginning, and must then consist only digits. E.g. `[1]` or `[42]`.
- **String**<br>
Must be wrapped in double quotes, `""`, and does not accept modifiers. E.g. `["foo"]` or `[""]`. As for [strings](#filtering-filter-filter_types-strings), double quotes inside any of the strings must be escaped by backslash, e.g. `["\""]`.

Syntaxes are (as you may have noticed) similar - but not always identical - to the previously described syntaxes for each of the data types.

##### Modifiers

- `/i`<br>
Makes searching for string values (exclusively) case insensitive.

Modifiers are appended **after** the last square bracket, e.g. `["foo", "BAR"]/i`, and applies to all elements in the array. E.g. all strings becoming case insensitive.

##### Samples

- `?filter[]=foo=[null]`
- `?filter[]=foo=[true,false]` (nonsensical)
- `?filter[]=foo=[null,"foo"]`
- `?filter[]=foo=[42,-42]`
- `?filter[]=foo=[3.14,-3.14]`
- `?filter[]=foo=["foo","BAR"]/i`

<a name="filtering-filter-operators"></a>
### Operators

Supported operators include:

- `=` (equal to)
- `!=` (not equal to)
- `>` (greater than)
- `>=` (greater than or equal to)
- `<` (less than)
- `<=` (less than or equal to)

Some operators are not usable with certain filtering options, though:

- [Booleans](#filtering-filter-filter_types-booleans) accept only `=`. I.e. `filter[]foo=iscool!=false` won't fly.
- [Null](#filtering-filter-filter_types-null), [substrings `LIKE`](#filtering-filter-filter_types-substrings_like), and [arrays `IN`](#filtering-filter-filter_types-arrays_in) accept only `=` and `!=`.

[Strings](#filtering-filter-filter_types-strings) do accept `>`, `>=`, `<`, `<=`, retaining the need for making comparison against certain values. E.g. dates.

In the [mapping](#mapping_and_validation), you may further [restrict the accepted operators](#mapping_and_validation-restricting_operators), e.g. so that an integer only accepts `=`. However, the specified operators must be one of the available operators for the respective [filter type](#filtering-filter-filter_types). Otherwise an [`\Kafoso\Questful\Exception\UnexpectedValueException`](src/Kafoso/Questful/Exception/UnexpectedValueException.php) is thrown.

<a name="filtering-filter_expression"></a>
## `?filterExpression`

By default, all filters are concatenated using the logical operator `AND`. I.e. only datasets, where all conditions are met, are returned. However, the `filterExpression` options allows control over the concatenation of the filters in a readable and manageable way.

### Accepted tokens

- Parentheses (`(` and `)`) to declare precedence.
- The logical operators:
  - `and`
  - `or`
  - `xor` (Exclusive or. One or the other must be true, but not both at once.)
- Digits (0-9), which represent the indexes in the `filter` option.

The expression must evaluate to PHP syntax. I.e. placement of the tokens, e.g. matching and proper closing of parentheses, is mandatory. Otherwise a [`\Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php) is thrown.

Indexes in `filter[]` and `filterExpression` must match. If a mismatch occurs a [`\Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php) is thrown.

### Example

URL:

```
?filter[]=foo="bar"&filter[]=foo="baz"&filterExpression=(0or1)
```

Resulting filter expression:

```
(foo = :filter_0 OR foo = :filter_1)
```

### Precedence

Precedence between `and`, `or`, and `xor` may vary depending on the storage unit. Even [the PHP language itself has differences](http://php.net/manual/en/language.operators.precedence.php) between operators `&&` and `||`, and operators `and` and `or`.

As a result, all expressions in Questful are normalized and strict precedence is enforced. This means - unless parentheses are purposely provided - expressions like `0or1and2` will be wrapped in precedence parentheses, becoming `0or(1and2)`. You may, however, specify the placement of the parentheses yourself, making an expression like `(0or1)and2` remain intact.

By enforcing precedence, different storage units should return identical results.

#### Precedence examples

| Input expression | Resulting normalized expression |
| --- | --- |
| `0and1or2xor3` | `(0and1)or(2xor3)` |
| `0or1and(2or3)` | `0or(1and(2or3))` |
| `0or(1and2)or3` | `(0or(1and2))or3` |

# Sorting

The sorting syntax is much simpler than that of the [filtering](#filtering) mechanism. Although, some of the same logic does carry over.

`?sort[]=foo` is an array, too, and indexes may be provided as the do for filters. I.e. `?sort[]=foo` is equivalent to `?sort[0]=foo`.

The order of indexes is **significant**. Lower indexes get prioritized first, starting from zero. I.e. in `?sort[0]=foo&sort[1]=bar`, sorting on `foo` is performed before sorting on `bar`.

Negative indexes (e.g. `?sort[-1]=foo`) will throw a [`Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php).

## Syntax

```
?sort[]=<key>
?sort[<index>]=<key>
?sort[]=<direction><key>
?sort[]=<key>/<modifier>
?sort[]=<direction><key>/<modifier>
?sort[<index>]=<direction><key>/<modifier>
```

, where:

- `<index>` is a positive, unique integer. Optional.
- `<direction>` is empty or "+" (plus), where sorting is performed in ascending order, or "-" (minus), where sorting is performed in descending order. Optional. [Details below](#sorting-direction).
- `<key>` is any (whitelisted) sorting key. Required.
- `<modifier>` is a [sorting modifier](#sorting-modifiers). Optional.

<a name="sorting-direction"></a>
## Direction

By default, sorting occurs in ascending order. However, this order may be changed by appending `-` (minus) to the target column name, e.g. `?sort[]=-foo`. For manageability and aesthetic reasons, both `+` (ascending) and `-` (descending) may be provided.

##### Samples

- `?sort[]=foo`
- `?sort[]=+foo`
- `?sort[]=-foo`

<a name="sorting-modifiers"></a>
## Modifiers

Modifiers including:

- `/i`<br>
Insensitive letter case.

### Examples

- `?sort[]=foo/i`
- `?sort[]=-foo/i`

Letter cases and non-English characters are handled solely by your storage unit. This may yield results, ordered in an undesirable fashion.

**A real world example:** The Danish special letter "Ø" is often being interpreted as "O". This is down right wrong, because in Danish, we have a total of three special letters - "Æ", "Ø", and "Å" - appended to the normal English alphabet. I.e. "...XYZÆØÅ". And so, the results matching "Ø" must come after "ZÆ" and not alongside "O".

Questful allows you to sort case insensitively and binary-safe (e.g. when using UTF-8), respecting special characters.

<a name="mapping_and_validation"></a>
# Mapping and validation

The class [`Kafoso\Questful\Model\Mapping`](src/Kafoso/Questful/Model/Mapping.php) creates a map between the keys provided via the HTTP request and the respective key/column in the storage, e.g. a column in a database. This map ensures shielding against injection attacks and reveals less information about the application and storage unit infrastructure.

Relating a filter or sort key to a column is performed with the `relate` method. You may wildly obfuscate the naming of keys in the client should you desire, as long as they're correctly mapped to tables and columns server-side.

Allowing specific filters, filter expressions, and/or sorts is performed with the `allow` method.

Usage example:

```php
<?php
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;

$queryParserFactory = new QueryParserFactory;
$queryParser = $queryParserFactory->createFromUri('?filter[]=foo="bar"');
$queryParser->parse();
$mapping = new Mapping($queryParser);
$mapping
    ->relate('foo', 't.foo')
    ->allow(new Allowable\Filter\AllowedStringFilter('foo'))
    ->validate();

// All is good - "foo" is allowed
```

In the above example, `$mapping->validate()` will throw a [`Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php) when a filter or sorting mismatch occurs. Calling `validate` is optional. However, filters - which have not been mapped - will be disregarded in the [bridging](#bridging_with_storage_units) logic.

<a name="mapping_and_validation-restricting_operators"></a>
## Restricting operators

Operators are provided as the 2nd argument in [`\Kafoso\Questful\Model\Mapping\Allowable\Filter\AbstractAllowedFilter`](src/Kafoso/Questful/Model/Mapping/Allowable/Filter/AbstractAllowedFilter.php), which accepts `null` or a non-empty array of strings. As mentioned previously, operators must be one of the available operators for the respective [filter type](#filtering-filter-filter_types). Otherwise an [`\Kafoso\Questful\Exception\UnexpectedValueException`](src/Kafoso/Questful/Exception/UnexpectedValueException.php) is thrown.

See [006-restrict-operators](#examples-006-restrict-operators) for a practical example.

### Sample

```php
<?php
use Kafoso\Questful\Model\Mapping\Allowable;

$mapping->allow(new Allowable\Filter\AllowedStringFilter("id", ["="]));
```

## Validators

Validators are used to confine the input data.

[Symfony's Validation bundle](https://symfony.com/doc/current/validation.html) is utilized for this purpose. Aside from being a solid library, you may define your own validation error messages, should you desire.

For a list of supported constraints, see: http://symfony.com/doc/current/reference/constraints.html

Some common use cases include:

- Minimum and maximum length of a string ([`Length`](http://symfony.com/doc/current/reference/constraints/Length.html)).
- Allowing or disallowing certain characters in a string ([`Regex`](http://symfony.com/doc/current/reference/constraints/Regex.html)).
- Expecting a certain string pattern, e.g. a specific date format ([`Regex`](http://symfony.com/doc/current/reference/constraints/Regex.html) and [`Callback`](http://symfony.com/doc/current/reference/constraints/Callback.html)).
- Expecting an integer to be within a range ([`Range`](http://symfony.com/doc/current/reference/constraints/Range.html)).
- Expecting an integer to be an exact value, e.g. a user's ID ([`EqualTo`](http://symfony.com/doc/current/reference/constraints/EqualTo.html)).

Validators are provided as the 3rd argument in [`\Kafoso\Questful\Model\Mapping\Allowable\Filter\AbstractAllowedFilter`](src/Kafoso/Questful/Model/Mapping/Allowable/Filter/AbstractAllowedFilter.php), which accepts `null` or an array of `\Symfony\Component\Validator\Constraint`.

All validators are applied upon calling [`\Kafoso\Questful\Model\Mapping->validate()`](src/Kafoso/Questful/Model/Mapping.php). If a violation occurs, a [`Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php) is thrown.

### Sample

```php
<?php
use Kafoso\Questful\Model\Mapping\Allowable;
use Symfony\Component\Validator\Constraints as Assert;

$mapping->allow(new Allowable\Filter\AllowedStringFilter("id", null, [
    new Assert\GreaterThan(1)
]));
```

For a more detailed example, see [Validators](#examples-007-validators) under [Examples](#examples).

<a name="bridging_with_storage_units"></a>
# Bridging with storage units

Bridging in Questful means translating the query values of the HTTP request to a format which is readable by a storage unit. And doing so in a safe manner.

A bridge (e.g. [`Kafoso\Questful\Model\Bridge\PdoMysql`](src/Kafoso/Questful/Model/Bridge/PdoMysql.php)) outputs a partial set of conditions, parameters, and sorting, which is consumable by the storage unit. For MySQL using PDO, a series of `WHERE` and `ORDER BY` conditions are produced as SQL strings, together with an array of parameters, which must be provided in a prepared statement.

The character encoding of bridges is "**UTF-8**" by default, but may be changed.

Usage sample:

```php
<?php
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Bridge\PdoMySql\PdoMySql5_5;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;

$queryParserFactory = new QueryParserFactory;
$queryParser = $queryParserFactory->createFromUri('?filter[42]=foo="bar"');
$queryParser->parse();
$mapping = new Mapping($queryParser);
$mapping
    ->relate('foo', 't.foo')
    ->allow(new Allowable\Filter\AllowedStringFilter("foo"));
$pdoMySql = new PdoMySql5_5($mapping);
$pdoMySql->generate();

var_dump($pdoMySql->toArray());
/**
 * Will output:
 *
 * array(3) {
 *   ["orderBy"]=>
 *   NULL
 *   ["parameters"]=>
 *   array(1) {
 *     ["filter_42"]=>
 *     string(3) "bar"
 *   }
 *   ["where"]=>
 *   string(27) "(t.foo = BINARY :filter_42)"
 * }
 */
```

Notice how the index **42** carries over and becomes part of the parameter identifier `filter_42`. These indexes are safe to use (i.e. no risk of injection) since they are force-converted to integers. A non-digit or negative index cause a [`Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php) to be thrown.

You are free to add additional conditions and sorting in the SQL sentence. Notice that the contents of `where` above is always wrapped in parentheses.

## Bridge types

The readily available bridges are:

- [`Kafoso\Questful\Model\Bridge\Doctrine\Doctrine2_1`](src/Kafoso/Questful/Model/Bridge/Doctrine/Doctrine2_1.php)<br>
For use with `Doctrine\ORM\QueryBuilder` (http://www.doctrine-project.org/). Doctrine version 2.1 and above.<br>
Requires handlers for `BINARY`. You may implement these yourself or simply use https://github.com/beberlei/DoctrineExtensions.<br>

- [`Kafoso\Questful\Model\Bridge\PdoMysql\PdoMysql5_5`](src/Kafoso/Questful/Model/Bridge/PdoMysql/PdoMysql5_5.php)<br>
For use with PDO MySQL versions 5.5 and above (http://php.net/manual/en/book.pdo.php).<br>

- [`Kafoso\Questful\Model\Bridge\PdoSqlite\PdoSqlite3`](src/Kafoso/Questful/Model/Bridge/PdoSqlite\PdoSqlite3.php)<br>
For use with PDO Sqlite3 (http://php.net/manual/en/book.sqlite3.php).<br>
Requires handlers for `XOR`. You may implement this yourself or simply use `Kafoso\Questful\Helper\Sqlite3Helper`. Notice `XOR` is a function, rather than an operator, and is as `XOR(0, 1)`.<br>


You may implement your own bridges by extending [`Kafoso\Questful\Model\Bridge\AbstractBridge`](src/Kafoso/Questful/Model/Bridge/AbstractBridge.php).

# Errors and exceptions

A few custom exceptions are utilized:

- [`Kafoso\Questful\Exception\BadRequestException`](src/Kafoso/Questful/Exception/BadRequestException.php)<br>
Thrown whenever the client input is malformed, out-of-bounds, or when attempting to target unmapped content. This exception goes hand-in-hand with the HTTP status code "**400 Bad Request**".
- [`Kafoso\Questful\Exception\InvalidArgumentException`](src/Kafoso/Questful/Exception/InvalidArgumentException.php)<br>
Same usage as the native `InvalidArgumentException`.
- [`Kafoso\Questful\Exception\RuntimeException`](src/Kafoso/Questful/Exception/RuntimeException.php)<br>
Same usage as the native `RuntimeException`. Used for programmatic oversights in the implementation of this library.
- [`Kafoso\Questful\Exception\UnexpectedValueException`](src/Kafoso/Questful/Exception/UnexpectedValueException.php)<br>
Same usage as the native `UnexpectedValueException`.

<a name="exception_codes"></a>
## Exception codes

Besides the above exception classes, the following exception codes are consistently used for each of the following namespace domains:

| Exception code | Namespace | Note |
| --- | --- | --- |
| <a name="error_code_1"></a>**1**          | `Kafoso\Questful\Model\QueryParser` | All exceptions within this class and all classes contained within this namespace. |
| <a name="error_code_4"></a>**4**          | `Kafoso\Questful\Model\Mapping` | All exceptions within this namespace. |
| <a name="error_code_5"></a>**5**          | `Kafoso\Questful\Model\Bridge` | All exceptions within this namespace. |

This concise format allow foreign code (i.e. code, which is not part of this library) to more accurately interpret and relay messages.

Perhaps developers wish to override a message (e.g. translate it to a different language), before returning it to the client. Or perhaps they wish to change the status code (e.g. to 404 Not Found) depending on the exception code. The options are there.

<a name="examples"></a>
# Examples

- <a name="examples-001-basic-filter-and-sorting"></a>**Basic filter and sorting**<br>
[examples/001-basic-filter-and-sorting.php](examples/001-basic-filter-and-sorting.php)<br>
Fundamental usage of Questful. Illustrates how to take a simple query, parse it, validate it, and produce metadata.
- <a name="examples-002-generating-a-mysql-parameterized-query"></a>**Generating a MySQL parameterized query**<br> [examples/002-generating-a-mysql-parameterized-query.php](examples/002-generating-a-mysql-parameterized-query.php)<br>
Illustrates how to generate a parameterized SQL statement, directly consumable by (PDO) MySQL.
- <a name="examples-003-using-filter-expression"></a>**Using filter expression**<br>
[examples/003-using-filter-expression.php](examples/003-using-filter-expression.php)<br>
Shows how to apply a simple filter expression (`filterExpression`). Selects all `id` between 10 and 99. Notice how the order of the filter changes; `filter_1` appears before `filter_0` due to the filter expression being `1and0`.
- <a name="examples-004-targeting-joined-sql-tables"></a>**Targeting joined SQL tables**<br>
[examples/004-targeting-joined-sql-tables.php](examples/004-targeting-joined-sql-tables.php)<br>
Targeting a joined table is no different than targeting the main table (`FROM`). In this example, we are extracting all users, created after a certain point in time (`u.timeCreated`), which are in a certain company (`c.id`); the joined table. Notice that the naming `c.id` is persisted in the URL for readability reasons. You may name it however you like; just remember to update the relation using the `relate` method.
- <a name="examples-005-conditional-mapping"></a>**Conditional mapping**<br>
[examples/005-conditional-mapping.php](examples/005-conditional-mapping.php)<br>
You may need to grant certain users special mapping permissions, e.g. based on an ACL. This example illustrates how to provide mapping for some users while restricting said mapping for others.
- <a name="examples-006-restrict-operators"></a>**Restrict operators**<br>
[examples/006-restrict-operators.php](examples/006-restrict-operators.php)<br>
Illustrates how to allow only a subset of operators in a filter.
- <a name="examples-007-validators"></a>**Validators**<br>
[examples/007-validators.php](examples/007-validators.php)<br>
Illustrates how to allow apply a series of validators to a mapped filter.
- <a name="examples-008-sqlite3"></a>**SQLite3 example**<br>
[examples/008-sqlite3.php](examples/008-sqlite3.php)<br>
An example using an actual database (SQLite3). Requires the sqlite3 extension enabled in PHP.
- <a name="examples-009-in-array"></a>**In array example**<br>
[examples/009-in-array.php](examples/009-in-array.php)<br>
An example using array (`IN`) to look up items in an SQLite3 database. Requires the sqlite3 extension enabled in PHP.

# Ideas and plans

- Expand number of ready-for-use bridges.
- Allow customization of HTTP parameter keys. I.e. make them changeable and not the static values of `?filter`, `?filterExpression`, and `?sort`.
- Allow common `AbstractAllowedFilter` configurations, where operators and validatores are stored and gets applied automatically. Useful if one wants to always restrict string length to - say - maximum 512 characters.
- (Re)introduce support for regular expressions, e.g. `?filter[]=foo=/^foo\d+$/i`. This comes with a myriad of security concerns, plus programmatic challenges such as differences between [POSIX 1003.2](http://www.regextester.com/eregsyntax.html) - as [used by MySQL](https://dev.mysql.com/doc/refman/5.7/en/regexp.html) - and [PCRE](http://php.net/manual/en/intro.pcre.php).

# Footnotes

<a name="footnotes-storage_unit"></a><sup>1</sup> Storage unit: Any means of persisting data, including databases, cache, file system storage, and more.

<a name="footnotes-denial_of_service_attacks"></a><sup>2</sup> (Distributed) Denial of Service (DDoS) - or simply DoS - attacks come in many forms. In respect to this library, one such DoS attack may be induced by long-running queries in the database. Using regular expressions, for instance, to search for content in a database is very costly and may induce long load times.
