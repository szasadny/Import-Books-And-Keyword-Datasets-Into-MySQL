# Import books and keyword datasets into mySQL

Given the books_import.csv and books_keywords.csv files prepared in the following repo:

https://github.com/szasadny/Keywords-For-Books-Data-Integration

I use two different php scripts to load these datasets into a mysql database of the following format:

![ERD Bibliotheek Database drawio](https://github.com/szasadny/Import-Books-And-Keyword-Datasets-Into-MySQL/assets/23632768/e65546fc-fbb9-4d82-9d36-edb4aeb21090)


The script takes into account that the database doesn't need to be empty and ensures that there are no duplicates in the book_keywords JOIN table. 

Make sure to run import_books.php before running import_keywords.php
