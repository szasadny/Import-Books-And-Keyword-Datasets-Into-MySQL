# Import books and keyword datasets into mySQL

Given the books_import.csv and books_keywords.csv files prepared in the following repo:

https://github.com/szasadny/Keywords-For-Books-Data-Integration

I use two php scripts to load these datasets into a mysql database of the following format:

TODO: ERD

The script takes into account that the database doesn't need to be empty and ensures that there are no duplicates in the book_keywords JOIN table. 

Make sure to run import_books.php before running import_keywords.php
