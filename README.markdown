sfInlineObjectPlugin
====================

This plugin defines a common syntax, or "token", that can be embedded into
any text and rendered in a flexible fashion. These tokens are referred to
as "inline objects" as each token can specify a unique identifier as well
as a collection of options. The inline object syntax is also very readable:

    [photo:flower width=100]

The power of the plugin is that it parses these inline objects for you
(with caching) and directs each type (e.g. photo, product) to a defined
method of rendering.

This is a wrapper for the [InlineObjectParser](http://github.com/weaverryan/InlineObjectParser)
and adds such things as:

 * caching
 * inline Doctrine objects

Consider the following examples:

    A picture of a flower: [photo:flower width=100].
    A picture of a flower: <img src="/images/flower.jpg" width="100" />

    The price of "My Product": [product:my-product display=price].
    The price of "My Product": $15.99.

Installation
------------

### Git

    git submodule add git://github.com/weaverryan/sfInlineObjectPlugin.git plugins/sfInlineObjectPlugin
    git submodule init
    git submodule update

Because the sfInlineObjectPlugin comes packaged with the InlineObjectParser
library as a submodule, you'll need to update the plugin's submodules as well:

    cd plugins/sfInlineObjectPlugin
    git submodule init
    git submodule update

### Subversion

First, bring in the plugin as an external:

    svn propedit svn:externals plugins
    
    // In the editor, add the following entry and then save
    sfInlineObjectPlugin https://svn.github.com/weaverryan/sfInlineObjectPlugin.git

Next, bring in the InlineObjectParser library (see note below):

    mkdir lib/vendor
    svn propedit svn:externals lib/vendor

    // In the editor, add the following entry and then save
    InlineObjectParser https://weaverryan@github.com/weaverryan/InlineObjectParser.git

Finally, update subversion by running `svn update`. You'll also need to
point the sfInlineObjectPlugin to your InlineObjectParser library. See
the section below titled "Optional configuration (or if installing via Subversion)".

>Because github does not yet support git submodules in svn, if using the
>svn method, the InlineObjectParser library won't come automatically with
>sfInlineObjectPlugin. For that reason, you need to download it manually
>to the lib/vendor/ directory.

### Setup

In your `config/ProjectConfiguration.class.php` file, make sure you have
the plugin enabled.

    public function setup()
    {
      // ...
      $this->enablePlugins('sfInlineObjectPlugin');
    }

### Optional configuration (or if installing via Subversion)

If you downloaded the [InlineObjectParser](http://github.com/weaverryan/InlineObjectParser)
separately, you can specify the path to the directory in `config/ProjectConfiguration.class.php`:

    public function setup()
    {
      // ...
      sfConfig::set('inline_object_dir', dirname(__FILE__).'/../lib/vendor/InlineObjectParser');
    }

Usage
-----

Once you've defined your object types (see below), using the plugin couldn't
be easier:

    echo parse_inline_object('A picture of a flower: [photo:flower width=100].');
    
    // Example output
    A picture of a flower: <img src="/images/flower.jpg" width="100" />

The only real work is to define your inline objects (e.g. photo, product)
and how exactly you want to render them.

Configuration
-------------

Each inline object is defined in `app.yml` with several options:

    all:
      inline_object:
        types:
          photo:
            class:    InlineObjectPhoto
            dir:      /images

The class that renders the inline object must extend the `sfInlineObjectType` class.
When an inline object of type `photo` is located, the `render()` method on
the corresponding class will be called:

    class InlineObjectPhoto extends sfInlineObjectType
    {
      public function render($name, $arguments)
      {
        $attrs = InlineObjectToolkit::arrayToAttributes($arguments);
        $dir = $this->getOption('dir', '/images');

        return sprintf('<img src="%s/%s.jpg"%s />', $dir, $name, $attrs);
      }
    }

Consider again the following example:

    A picture of a flower: [photo:flower width=100].

When rendering this inline object, `$name` will be equal to `flower` and
`$arguments` will be `array('width' => 50)`. Notice also the `dir` key
is made available inside the class via the `->getOption()` method.

Another common method of rendering an inline object is to specify a
`partial` option. In this case, the specified partial will be called
whenever an inline object of that type is found:

    all:
      inline_object:
        types:
          photo:
            partial:  myModule/myTemplate
            dir:      /images

In the `myModule/_myTemplate.php` file:

    <?php $dir = $inline_object->getOption('dir', '/images') ?>
    <?php echo image_tag($dir.'/'.$name, $arguments) ?>

The following variables are passed to the template:

 * `$name` The name of the inline object (e.g. flower)
 * `$arguments` Any arguments included in the inline object (e.g. the width array)
 * `$inline_object` The original `sfInlineObjectType` object.

>Internally, if the `class` key is not specified in `app.yml`, it defaults
>to`sfInlineObjectType`. By default, `sfInlineObjectType` attempts to render
>the object via the `partial` option. 


Linking to foreign Doctrine objects
-----------------------------------

A great feature of this plugin is to create inline objects that represent
doctrine database objects. This allows you to embed and render Doctrine
objects using the inline object syntax. If setup correctly, you can even
do this without causing extra queries to each inline foreign object.

Consider this example:

    The price of "My Product": [product:my-product display=price].

Suppose that in this example, we want to retrieve a Doctrine record from
a model called `Product` whose slug is `my-product` and then render based
on its data. This can be done quite easily.

### The simple setup

When defining an inline object type that represents a foreign Doctrine object,
you simply need to specify two additional keys in `app.yml`: `model` and `key_column`:

    all:
      inline_object:
        types:
          product:
            class:  InlineObjectProduct
            model:  Product
            key_column: slug

Inside `InlineObjectProduct`, you can now use a method called `getRelatedObject()`.
This method returns the related instance represented by the inline object:

    class InlineObjectProduct extends sfInlineObjectType
    {
      public function render($name, $arguments)
      {
        $product = $this->getRelatedObject($name);

        // ...
      }
    }

This will attempt to minimize the number of queries needed as much as possible
(by grouping queries). Still, each string that's parsed will need one extra
query per inline object type.

### The complete setup

Generally, the text that contains the inline objects will be stored in the
database on some model. For example, suppose we have a `Blog` model whose
`body` field contains inline objects that related to the `Product` model.
If setup correctly, those related `Product` objects can be returned with
_no_ extra queries.

To do this, we define a real many-to-many database relationship between
`Blog` and `Product`. When processing data from a `Blog` record, any inline
object that relates to `Product` is stored in this relationship. If `Blog` is
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

>Note that the `Blog` model uses the `sfInlineObjectContainerTemplate` behavior.
>This enforces garbage collection which removes old `Product` objects from the
>`Products` relation.

Using the parser itself requires just one extra step. Namely, we need to
tell the parser about the source `Blog` entry to use when storing the
related `Product` objects:

    $blog = Doctrine_Core::getTable('Blog')->find(1);

    echo parse_inline_object($blog->body, $blog);

Caching
-------

The parsing of strings is done via regular expressions and can drastically
hurt performance for large text. For that reason, caching is made to be
easy and harmless.

Caching is enabled by default, but can be configured via `app.yml`:

    all:
      inline_object:
        cache:
          enabled:  true
          class:    sfFileCache
          options:
            cache_dir:  <?php echo sfConfig::get('sf_app_cache_dir') ?>/inline_objects

Using with sfContentFilterPlugin
--------------------------------

[sfContentFilterPlugin](http://github.com/weaverryan/sfContentFilterPlugin)
is a general-purpose content filter that can be used with this plugin.
For example, by include the content filter plugin, you could convert
markdown to html, change urls to anchor tags, and parse inline objects
all at once.

Once you've installed `sfContentFilterPlugin`, you're done! A filter called
`inline_object` will now be available as a normal filter for use with
`sfContentFilterPlugin`.

    echo filter_content($content, 'inline_object');

If you're using Doctrine inline objects and need to relate a piece of
content to a Doctrine record (see above "The complete setup"), you'll
have one additional step, which can be accomplished in an action or in
the view:

    // in an action
    $this->getInlineObjectParser()->setDoctrineRecord($blog);
    
    // in the view
    get_inline_object_parser()->setDoctrineRecord($blog);

The normal `filter_content()` call can then be placed anywhere after this.

The Fine Details
----------------

This plugin was taken from [sympal CMF](http://www.sympalphp.org) and was
developed by both Jon Wage and Ryan Weaver.

A bug tracker is available at
[http://redmine.sympalphp.org/projects/inlineobject](http://redmine.sympalphp.org/projects/inlineobject).

If you have questions, comments or anything else, email me at ryan.weaver [at] iostudio.com

