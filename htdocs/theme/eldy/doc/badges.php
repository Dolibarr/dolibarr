<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Documentation and examples for theme.">
	
	<link href="../style.css.php" rel="stylesheet">
	<link href="doc.css" rel="stylesheet">
  </head>
  <body class="docpage" >  
  
  <main  role="main"  >
          <h1 class="bd-title" id="content">Badges</h1>
          <p class="bd-lead">Documentation and examples for badges, our small count and labeling component.</p>

          <h2 id="example">Example</h2>

		<p>Badges scale to match the size of the immediate parent element by using relative font sizing and <code class="highlighter-rouge">em</code> units.</p>
		
		<div class="bd-example">
		<h1>Example heading <span class="badge badge-secondary">New</span></h1>
		<h2>Example heading <span class="badge badge-secondary">New</span></h2>
		<h3>Example heading <span class="badge badge-secondary">New</span></h3>
		<h4>Example heading <span class="badge badge-secondary">New</span></h4>
		<h5>Example heading <span class="badge badge-secondary">New</span></h5>
		<h6>Example heading <span class="badge badge-secondary">New</span></h6>
		</div>
		
		<figure class="highlight">
    		<pre>
        		<code class="language-html" data-lang="html">
&lt;h1&gt;Example heading &lt;span class=&quot;badge badge-secondary&quot;&gt;New&lt;/span&gt;&lt;/h1&gt;
&lt;h2&gt;Example heading &lt;span class=&quot;badge badge-secondary&quot;&gt;New&lt;/span&gt;&lt;/h2&gt;
&lt;h3&gt;Example heading &lt;span class=&quot;badge badge-secondary&quot;&gt;New&lt;/span&gt;&lt;/h3&gt;
&lt;h4&gt;Example heading &lt;span class=&quot;badge badge-secondary&quot;&gt;New&lt;/span&gt;&lt;/h4&gt;
&lt;h5&gt;Example heading &lt;span class=&quot;badge badge-secondary&quot;&gt;New&lt;/span&gt;&lt;/h5&gt;
&lt;h6&gt;Example heading &lt;span class=&quot;badge badge-secondary&quot;&gt;New&lt;/span&gt;&lt;/h6&gt;
        		</code>
    		</pre>
		</figure>
		
		<p>Badges can be used as part of links or buttons to provide a counter.</p>
		
		<div class="bd-example">
		<button type="button" class="button">
		  Notifications <span class="badge badge-primary">4</span>
		</button>
		</div>
		
		<figure class="highlight"><pre><code class="language-html" data-lang="html">
&lt;button type=&quot;button&quot; class=&quot;button&quot;&gt;
  Notifications &lt;span class=&quot;badge badge-primary&quot;&gt;4&lt;/span&gt;
&lt;/button&gt;
		</code></pre></figure>
		
		<div class="warning">		
		<p>Note that depending on how they are used, badges may be confusing for users of screen readers and similar assistive technologies. While the styling of badges provides a visual cue as to their purpose, these users will simply be presented with the content of the badge. Depending on the specific situation, these badges may seem like random additional words or numbers at the end of a sentence, link, or button.</p>
		
		<p>Unless the context is clear (as with the “Notifications” example, where it is understood that the “4” is the number of notifications), consider including additional context with a visually hidden piece of additional text.</p>
		
		<p><strong>Remember to use aria-label attribute for accessibility in Dolibarr. Don't forget to use aria-hidden on icons included in badges</strong></p>
		</div>

		<div class="bd-example">
		<button type="button" class="btn btn-primary">
		  Profile <span class="badge badge-light" aria-label="9 unread messages" >9</span>
		  <span class="sr-only">unread messages</span>
		</button>
		</div>
		
		<figure class="highlight">
    		<pre>
        		<code class="language-html" data-lang="html">
        		
&lt;button type=&quot;button&quot; class=&quot;btn btn-primary&quot;&gt;
  Profile &lt;span class=&quot;badge badge-light&quot; aria-label=&quot;9 unread messages&quot; &gt;9&lt;/span&gt;
  &lt;span class=&quot;sr-only&quot;&gt;unread messages&lt;/span&gt;
&lt;/button&gt;
        		
        		</code>
    		</pre>
		</figure>
		
		<h2 id="contextual-variations">Contextual variations</h2>
		
		<p>Add any of the below mentioned modifier classes to change the appearance of a badge.</p>
		
		<div class="bd-example">
		
		<span class="badge badge-primary">Primary</span>
		<span class="badge badge-secondary">Secondary</span>
		<span class="badge badge-success">Success</span>
		<span class="badge badge-danger">Danger</span>
		<span class="badge badge-warning">Warning</span>
		<span class="badge badge-info">Info</span>
		<span class="badge badge-light">Light</span>
		<span class="badge badge-dark">Dark</span>
		</div>
		
		<figure class="highlight">
    		<pre>
        		<code class="language-html" data-lang="html">
&lt;span class=&quot;badge badge-primary&quot;&gt;Primary&lt;/span&gt;
&lt;span class=&quot;badge badge-secondary&quot;&gt;Secondary&lt;/span&gt;
&lt;span class=&quot;badge badge-success&quot;&gt;Success&lt;/span&gt;
&lt;span class=&quot;badge badge-danger&quot;&gt;Danger&lt;/span&gt;
&lt;span class=&quot;badge badge-warning&quot;&gt;Warning&lt;/span&gt;
&lt;span class=&quot;badge badge-info&quot;&gt;Info&lt;/span&gt;
&lt;span class=&quot;badge badge-light&quot;&gt;Light&lt;/span&gt;
&lt;span class=&quot;badge badge-dark&quot;&gt;Dark&lt;/span&gt;
        		</code>
    		</pre>
		</figure>
		
		<div class="warning">
		<h5 id="conveying-meaning-to-assistive-technologies">Conveying meaning to assistive technologies</h5>
		
		<p>Using color to add meaning only provides a visual indication, which will not be conveyed to users of assistive technologies – such as screen readers. Ensure that information denoted by the color is either obvious from the content itself (e.g. the visible text), or is included through alternative means, such as additional text hidden with the <code class="highlighter-rouge">.sr-only</code> class.</p>
		</div>
		
		

		<h2 id="contextual-variations">Default status</h2>
		
		<p>Add any of the below mentioned modifier classes to change the appearance of a badge to be linked to a default status.</p>
		
		<div class="bd-example">
		<?php for ($i = 0; $i <= 9; $i++): ?>
		<span class="badge badge-status-<?php print $i;  ?>" >status-<?php print $i;  ?></span>
		<?php endfor; ?>
		</div>

		<figure class="highlight"><pre><code class="language-html" data-lang="html"><pre><code class="language-html" data-lang="html">
<?php for ($i = 0; $i <= 9; $i++): ?>
&lt;span class="badge badge-status-<?php print $i;  ?>" &gt;status-<?php print $i;  ?>&lt;/span&gt;
<?php endfor; ?>
		</code></pre></figure>
		
				
		<h2 id="pill-badges">Pill badges</h2>
		
		<p>Use the <code class="highlighter-rouge">.badge-pill</code> modifier class to make badges more rounded (with a larger <code class="highlighter-rouge">border-radius</code> and additional horizontal <code class="highlighter-rouge">padding</code>).</p>
		
		<div class="bd-example">
		
		<span class="badge badge-pill badge-primary">Primary</span>
		<span class="badge badge-pill badge-secondary">Secondary</span>
		<span class="badge badge-pill badge-success">Success</span>
		<span class="badge badge-pill badge-danger">Danger</span>
		<span class="badge badge-pill badge-warning">Warning</span>
		<span class="badge badge-pill badge-info">Info</span>
		<span class="badge badge-pill badge-light">Light</span>
		<span class="badge badge-pill badge-dark">Dark</span>
		
		<?php for ($i = 0; $i <= 9; $i++): ?>
		<span class="badge badge-pill badge-status-<?php print $i;  ?>" >status-<?php print $i;  ?></span>
		<?php endfor; ?>
		
		</div>
		
		<figure class="highlight">
		<pre>
		<code class="language-html" data-lang="html">
&lt;span class=&quot;badge badge-pill badge-primary&quot;&gt;Primary&lt;/span&gt;
&lt;span class=&quot;badge badge-pill badge-secondary&quot;&gt;Secondary&lt;/span&gt;
&lt;span class=&quot;badge badge-pill badge-success&quot;&gt;Success&lt;/span&gt;
&lt;span class=&quot;badge badge-pill badge-danger&quot;&gt;Danger&lt;/span&gt;
&lt;span class=&quot;badge badge-pill badge-warning&quot;&gt;Warning&lt;/span&gt;
&lt;span class=&quot;badge badge-pill badge-info&quot;&gt;Info&lt;/span&gt;
&lt;span class=&quot;badge badge-pill badge-light&quot;&gt;Light&lt;/span&gt;
&lt;span class=&quot;badge badge-pill badge-dark&quot;&gt;Dark&lt;/span&gt;
<?php for ($i = 0; $i <= 9; $i++): ?>
&lt;span class="badge badge-pill badge-status-<?php print $i;  ?>" &gt;status-<?php print $i;  ?>&lt;/span&gt;
<?php endfor; ?>
		</code></pre></figure>
		
		
		
		<h2 id="dot-badges">Dot badges</h2>
		
		<p>Use the <code class="highlighter-rouge">.dot-pill</code> modifier class to make badges circle.</p>
		
		<div class="bd-example">
		
		<span class="badge badge-dot badge-primary"></span>
		<span class="badge badge-dot badge-secondary"></span>
		<span class="badge badge-dot badge-success"></span>
		<span class="badge badge-dot badge-danger"></span>
		<span class="badge badge-dot badge-warning"></span>
		<span class="badge badge-dot badge-info"></span>
		<span class="badge badge-dot badge-light"></span>
		<span class="badge badge-dot badge-dark"></span>
		
		<?php for ($i = 0; $i <= 9; $i++): ?>
		<span class="badge badge-dot badge-status-<?php print $i;  ?>" ></span>
		<?php endfor; ?>
		
		</div>
		
		<figure class="highlight">
		<pre>
		<code class="language-html" data-lang="html">
&lt;span class=&quot;badge badge-dot badge-primary&quot;&gt;Primary&lt;/span&gt;
&lt;span class=&quot;badge badge-dot badge-secondary&quot;&gt;Secondary&lt;/span&gt;
&lt;span class=&quot;badge badge-dot badge-success&quot;&gt;Success&lt;/span&gt;
&lt;span class=&quot;badge badge-dot badge-danger&quot;&gt;Danger&lt;/span&gt;
&lt;span class=&quot;badge badge-dot badge-warning&quot;&gt;Warning&lt;/span&gt;
&lt;span class=&quot;badge badge-dot badge-info&quot;&gt;Info&lt;/span&gt;
&lt;span class=&quot;badge badge-dot badge-light&quot;&gt;Light&lt;/span&gt;
&lt;span class=&quot;badge badge-dot badge-dark&quot;&gt;Dark&lt;/span&gt;
<?php for ($i = 0; $i <= 9; $i++): ?>
&lt;span class="badge badge-dot badge-status-<?php print $i;  ?>" &gt;status-<?php print $i;  ?>&lt;/span&gt;
<?php endfor; ?>
		</code></pre></figure>
		
		
<div class="warning">		
		<p>Note that depending on how they are used, badges may be confusing for users of screen readers and similar assistive technologies. While the styling of badges provides a visual cue as to their purpose, these users will simply be presented with the content of the badge. Depending on the specific situation, these badges may seem like random additional words or numbers at the end of a sentence, link, or button.</p>
		
		<p>Unless the context is clear (as with the “Notifications” example, where it is understood that the “4” is the number of notifications), consider including additional context with a visually hidden piece of additional text.</p>
		
		<p><strong>Remember to use aria-label attribute for accessibility in Dolibarr. Don't forget to use aria-hidden on icons included in badges</strong></p>
</div>		
		
		
		<h2 id="links">Links</h2>
		
		<p>Using the contextual <code class="highlighter-rouge">.badge-*</code> classes on an <code class="highlighter-rouge">&lt;a&gt;</code> element quickly provide <em>actionable</em> badges with hover and focus states.</p>
		
		<div class="bd-example">
		
		<a href="#" class="badge badge-primary">Primary</a>
		<a href="#" class="badge badge-secondary">Secondary</a>
		<a href="#" class="badge badge-success">Success</a>
		<a href="#" class="badge badge-danger">Danger</a>
		<a href="#" class="badge badge-warning">Warning</a>
		<a href="#" class="badge badge-info">Info</a>
		<a href="#" class="badge badge-light">Light</a>
		<a href="#" class="badge badge-dark">Dark</a>
		<?php for ($i = 0; $i <= 9; $i++): ?>
		<a href="#" class="badge badge-status-<?php print $i;  ?>" >status-<?php print $i;  ?></a>
		<?php endfor; ?>
		
		</div>
		
		<figure class="highlight"><pre><code class="language-html" data-lang="html">
&lt;a href=&quot;#&quot; class=&quot;badge badge-primary&quot;&gt;Primary&lt;/a&gt;
&lt;a href=&quot;#&quot; class=&quot;badge badge-secondary&quot;&gt;Secondary&lt;/a&gt;
&lt;a href=&quot;#&quot; class=&quot;badge badge-success&quot;&gt;Success&lt;/a&gt;
&lt;a href=&quot;#&quot; class=&quot;badge badge-danger&quot;&gt;Danger&lt;/a&gt;
&lt;a href=&quot;#&quot; class=&quot;badge badge-warning&quot;&gt;Warning&lt;/a&gt;
&lt;a href=&quot;#&quot; class=&quot;badge badge-info&quot;&gt;Info&lt;/a&gt;
&lt;a href=&quot;#&quot; class=&quot;badge badge-light&quot;&gt;Light&lt;/a&gt;
&lt;a href=&quot;#&quot; class=&quot;badge badge-dark&quot;&gt;Dark&lt;/a&gt;
<?php for ($i = 0; $i <= 9; $i++): ?>
&lt;a class="badge badge-status-<?php print $i;  ?>" &gt;status-<?php print $i;  ?>&lt;/a&gt;
<?php endfor; ?>
		</code></pre></figure>


		
		
		
		
		
        </main>
  
  </body>
</html>