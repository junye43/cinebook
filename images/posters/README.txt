HOW TO ADD MOVIE POSTERS
========================

1. Put your poster image files in THIS folder:
   /Applications/XAMPP/xamppfiles/htdocs/cinebook/images/posters/

   Allowed types: .jpg .jpeg .png .webp .gif
   Tip: use portrait images (2:3 ratio, e.g. 400x600) for the cards.
   Use your own or royalty-free images (do not use copyrighted studio posters).

2. Tell the database which file belongs to which movie by setting the
   movie's "Poster" column to the file name (NOT the full path).

   Example — in phpMyAdmin > SQL tab, or the terminal mysql client:

       UPDATE movie SET Poster = 'stellar_horizon.jpg' WHERE Title = 'Stellar Horizon';
       UPDATE movie SET Poster = 'last_lighthouse.png' WHERE Title = 'The Last Lighthouse';

3. Refresh the site. Cards, the hero, the thumbnail rail and the movie
   details banner will automatically use the image.

If a movie has no Poster (or the file is missing), the site falls back to the
built-in genre-coloured placeholder — so nothing breaks if you leave some blank.
