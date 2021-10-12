# glossary

To use this module, simply replace any HTMLText field in your templates like this:

Before:
```html
$Content
$OtherContent
```

After:
```html
$Content.Annotated
$OtherContent.Annotated
```

This will then add descriptions to terms you enter in the CMS. You can style
these annotations as you see fit.

You can also annotate terms once per HTML block
```html
$Content.AnnotatedOncePerTerm
```

```

## js example

```js
/* eslint-disable */
import jQuery from 'jquery';
/* eslint-enable */

export default function () {

    //mobile
    jQuery('button.glossary-button').on(
        'click',
        function () {
            jQuery(this).toggleClass('active');
        }
    );

    jQuery('button.close-popup').on(
        'click',
        function () {
            jQuery('.glossary-button').removeClass('active');
        }
    );
}
```

# scss example:

```scss
/ re: @media (hover: hover) {
// https://github.com/greglobinski/gatsby-starter-hero-blog/issues/27
// does not work on FIREFOX: https://caniuse.com/#feat=css-media-interaction
// solution coming.
// alternative solutions: http://www.javascriptkit.com/dhtmltutors/sticky-hover-issue-solutions.shtml


cite {
    font-style: normal;
}
.glossary-button-and-annotation-holder {
    position: relative;

}

.glossary-annotation-holder {
    display: none;
    position: fixed;
    left: calc(50% - 150px);
    bottom: 40px;
    width: 300px;
    padding: 22px 19px 22px 26px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0px 0px 60px 0px rgba(0, 0, 0, 0.1);
    text-align: left;
    z-index: 40;
    display: block;
    visibility: hidden;

    @include bp(min-sm){
        @media (hover: hover) {
            transition: 0s 0.5s;
        }
        position: absolute;
        top: 25px;
        left: 0;
        bottom: auto;
        width: 260px;
        padding: 26px 29px 29px;
        transform: translate3d(0,30px,0);

        &:after {
            content: '';
            position: absolute;
            top: -25px;
            left: 45px;
            width: 0;
            height: 0;
            border-left: 25px solid transparent;
            border-right: 25px solid transparent;
            border-bottom: 25px solid white;
            margin-left: -28px;
        }
    }

    @include bp(min-lg){
        left: 50%;
        margin-left: -130px;
        &:after {
            left: 50%;
        }
    }

    dfn {
        display: block;
        @media (hover: none)
        {
            margin-right: 20px;
        }
        font-size: 1.8rem;
        @include bp(min-sm){
            font-size: 2rem;
        }
    }

    a {
        font-size: 1.8rem;
        cursor: pointer;
    }

}

.close-popup {
    display: inline;
    position: absolute;
    right: 16px;
    top: 25px;
    padding: 0;

    @media (hover: hover)
    {
        display: none;
    }

    svg {
        width: 23px;
        height: 23px;
        fill: $bay-of-many;
        @include bp(min-lg){
            display: none;
        }
    }
}

.term-def {
    display: inline-block;
    margin: 4px 0 8px;
    font-size: 1.7rem;
    line-height: 1.45;
    @include bp(min-sm){
        font-size: 1.8rem;
        margin: 6px 0 24px;
    }
}

.glossary-button {
    position: relative;
    padding: 0;
    border-bottom: 2px dashed $shark;
    font-size: 1.8rem;
    font-weight: 300;
    line-height: 0.8;

    @include bp(min-sm){
        font-size: 2.0rem;
        line-height: 1.1;
    }


    &:hover, &:focus {
        outline: none;
    }

}


.glossary-button-and-annotation-holder{
    &:hover {
        & > .glossary-annotation-holder {
            @media (hover: hover) {
                visibility: visible;
                height: auto;
                transition-delay: 0s;
            }
        }
    }

    .glossary-button.active {
        + .glossary-annotation-holder {
            visibility: visible;
            height: auto;
            transition-delay: 0s;
        }
    }
}


```
