/* CMD for sass compile eldy: sass --watch scss/carambarr.scss:malabar.php.css  */
/* Need sass installation  */
:root {
  --borderWidth: 1px;
  --opacity-soft: 0.15; }

ul.blockvmenu {
  color: var(--colortextbackvmenu);
  margin: 0;
  list-style: none;
  line-height: 1.5em;
  padding: 0;
  position: relative; }
  ul.blockvmenu > li {
    display: block; }
  ul.blockvmenu > .menu_titre > * {
    display: flex;
    align-items: center;
    padding: 0.5em 0;
    gap: 0.5em; }
    ul.blockvmenu > .menu_titre > * > i.fa {
      font-size: 1em; }
  ul.blockvmenu:before, ul.blockvmenu:last-of-type:after {
    content: "";
    opacity: var(--opacity-soft);
    width: 100%;
    height: var(--borderWidth);
    z-index: 1;
    background: currentColor;
    display: block;
    margin: 1em 0;
    /* position:absolute; */
    /* top:0; */ }

/*# sourceMappingURL=malabar.css.php.map */
