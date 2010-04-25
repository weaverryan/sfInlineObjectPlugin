sfInlineObjectPlugin
====================

Defines a common syntax that can be used to embed objects inline. This text
can then be parsed and the inline objects rendered.

Consider the following examples:

    A picture of a flower: [photo:flower width=100].
    A picture of a flower: <img src="/images/flower.jpg" width="100" />

    The price of "My Product": [product:my-product display=price].
    The price of "My Product": $15.99.

All the developer needs to do is define a syntax (e.g. `photo` or `product`)
and a class (of type `InlineObjectType`) that will render matches of that type.

Usage
-----

Once you've defined your object types (see below), using the plugin couldn't
be easier:

    $parser = new sfInlineObjectParser();
    echo $parser->parse('A picture of a flower: [photo:flower width=100].');
    
    // Example output
    A picture of a flower: <img src="/images/flower.jpg" width="100" />

Configuration
-------------

To get started, simply define your an inline object syntax in `app.yml` and
create a class to render it:

    all:
      inline_object:
        types:
          photo: InlineObjectPhoto

The class that renders the syntax must extend the abstract class `sfInlineObjectType`.
The only method that needs to be defined is `render()`:

    class InlineObjectPhoto extends sfInlineObjectType
    {
      public function render()
      {
        $attrs = InlineObjectToolkit::arrayToAttributes($this->getOptions());

        return sprintf('<img src="/images/%s.jpg"%s />', $this->getName(), $attrs);
      }
    }

Consider again the following example:

    A picture of a flower: [photo:flower width=100].

When rendering this match, `->getName()` will be equal to `flower` and
`->getOptions()` will be the `array('width' => 50)`.

By using `->getName()` and `->getOptions()`, the inline object can be
rendered in any way.

Linking to foreign Doctrine objects
-----------------------------------

You can also link to foreign Doctrine objects inline. If setup correctly,
you can do this without causing extra queries to each inline foreign object.

Consider the previous example:

    The price of "My Product": [product:my-product display=price].

Suppose that in this example, we want to retrieve a Doctrine record from
a model called `Product` whose slug is `my-product` and then render based
on its data. This can be done quite easily.

### The simple setup

When defining an inline object type that represents a foreign Doctrine object,
the class should extend `sfInlineObjectDoctrineType` instead of `sfInlineObjectDoctrine`.
You'll need to define two new methods, `getModel()` and `getKeyColumn()`:

    class InlineObjectProduct extends sfInlineObjectDoctrineType
    {
      public function getModel()
      {
        return 'Product';
      }
      
      public function getKeyColumn()
      {
        return 'slug';
      }
      
      public function render()
      {
        $product = $this->getRelatedObject();

        // ...
      }
    }

Inside `InlineObjectProduct`, you now have access to a new method, `getRelatedObject()`
that will return the related instance represented by the inline object:

This will attempt to minimize the number of queries needed as much as possible.
Still, each string that's parsed will need one extra query per inline object type.

### The complete setup

Generally, the text that contains the inline objects will be stored in the
database on some model. For example, suppose we have a `Blog` model whose
`body` field contains inline objects that related to the `Product` model.
If setup correctly, those related `Product` objects can be returned with
_no_ extra queries.

To do this, we define a real database relationship between `Blog` and
`Product`. When processing data from a `Blog` record, any inline object
that relates to `Product` is stored in this relationship. If `Blog` is
properly joined to `Product`, the embedded `Product` objects will be retrieved
with no additional queries.

First, define the many-to-many relationship:

    Blog:
      columns:
        title:    string(255)
        body:     clob
      relations:
        Products:
          class: Product
          refClass: BlogProduct
          local: blog_id
          foreign: product_id
      actAs:
        sfInlineObjectContainerTemplate:
          relations:    [Products]
    
    BlogProduct:
      columns:
        blog_id:
          type:     integer
          primary:  true
        product_id:
          type:     integer
          primary:  true

Note that the `Blog` model uses the `sfInlineObjectContainerTemplate` behavior.
This enforces garbage collection which removes old `Product` objects from the
`Products` relation.

Next, when defining the `product` inline object type, we need to specify
a new `relations` configuration.

    all:
      inline_object:
        types:
          product:  InlineObjectProduct

The `relations` key tells the parser to use the `Products` relationship
on `Blog` to retrieve `Product` records instead of querying for them directly.

Using the parser itself requires just one extra step:

    $blog = Doctrine_Core::getTable('Blog')->find(1);
    $parser = new sfInlineObjectParser();
    $parser->setDoctrineRecord($blog);
    echo $parser->parse($blog->body);

Caching
-------

The parsing of strings is done via regular expressions and can drastically
hurt performance for large text. For that reason, the parsing of strings
can be cached.

    $text = 'The price of "My Product": [product:my-product display=price].';
    $cacheKey = 'product_price_description';

    $parser = new sfInlineObjectParser();
    echo $parser->parse($text, $cache_key);

The parsing of the string will occur once and then be cached. Only the
string parsing is cached, so if the inline object itself refers to a dynamic
object, it won't cause any problems. In other words, there's probably not
a good reason to NOT use this.

To configure the cache, edit your `app.yml` file:

    all:
      inline_object:
        cache:
          enabled:  true
          class:    sfFileCache
          options:
            cache_dir:  <?php echo sfConfig::get('sf_app_cache_dir') ?>/inline_objects

The Fine Details
----------------

This plugin was taken from [sympal CMF](http://www.sympalphp.org) and was
developed by both Jon Wage and Ryan Weaver.

This plugins uses the [InlineObjectParser](http://github.com/weaverryan/InlineObjectParser)
library.

If you have questions, comments or anything else, email me at ryan [at] thatsquality.com

