Term Gen
========

Term Generator. Create and assign lorem ipsum terms.

Term gen is based on the excellent [Post Generator](https://github.com/trepmal/post-gen) by trepmal.

```
wp term-gen create <taxonomy>
```

Includes a text file with lorem ipsum for randomizing the term names. 

## Create terms

Use the `create` subcomand to create terms. There are 2 options it always accepts. 

```
SYNOPSIS

  wp term-gen create <taxonomy> [--count=<number>] [--max_depth=<number>]

OPTIONS

  <taxonomy>
    Number of terms

  [--count=<number>]
    How many terms to generate. Default: 100
	 
  [--max_depth=<number>]
    Generate child terms down to a certain depth. Default: 1

EXAMPLES

    wp term-gen create category --count=50 --max_depth=6

```

## Assigning terms
Use the `assign` subcomand to assign terms to posts. There are 3 options it always accepts.


```
SYNOPSIS

  wp term-gen assign <taxonomy> [--max-terms=<number>] [--posts=<number>] [--post-type=<post-type>]

OPTIONS

  <taxonomy>
    The taxonomy used to assign terms to posts.

  [--max-terms=<number>]
    How many terms to assign per post. Default random max terms: 8

  [--posts=<number>]
    How many posts to assign terms to. Default: 100

  [--post-type=<post-type>]
    Post type to assign taxonomy terms to. Default: 'post'

EXAMPLES

    wp term-gen assign post_tag --max-terms=9 --posts=100 --taxonomy=post_tag

```
