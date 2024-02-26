<?php

namespace App\Http\Controllers;

use App\Models\Authors;
use App\Models\Book_Authors;
use App\Models\Book_Genres;
use App\Models\Books;
use App\Models\Genres;
use App\Models\User_Favorites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    public function showAll()
    {
        if (!Books::exists())
            return response()->json('No books found', 404);

        return Books::all();
    }

    public function add(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'genres' => 'required|string',
            'authors' => 'required|string'
        ]);

        // Handle genres
        $genres = explode(',', $request->input('genres'));
        foreach ($genres as $genre) {
            $genre = $this->sanitizeAndValidateGenre($genre);

            $genreModel = Genres::firstOrCreate(['genre' => $genre]);
        }

        // Handle authors
        $authors = explode(',', $request->input('authors'));
        foreach ($authors as $author) {
            $author = $this->sanitizeAndValidateAuthor($author);

            $authorModel = Authors::firstOrCreate(['name' => $author]);
        }

        // Create book
        $book = Books::create(['name' => $request->input('name')]);

        // Add book to Book_Genres table
        foreach ($genres as $genre) {
            $this->associateBookWithGenre($book, $genre);
        }

        // Add book to Book_Authors table
        foreach ($authors as $author) {
            $this->associateBookWithAuthor($book, $author);
        }

        return response()->json(['book' => $book], 201);
    }
    public function showById($id)
    {
        $book = $this->validateBook($id);

        return response()->json($book);
    }
    public function remove($id)
    {
        $book = $this->validateBook($id);

        User_Favorites::where('book_id', $id)->delete();
        Book_Authors::where('book_id', $id)->delete();
        Book_Genres::where('book_id', $id)->delete();

        $book->delete();

        return response()->json('Book deleted successfully', 201);
    }
    public function csvExport()
    {
        $books = Books::all();

        $csvFileName = 'exported_books.csv';
        $filePath = storage_path($csvFileName);
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $handle = fopen($filePath, 'w');
        fputcsv($handle, ['id', 'name', 'created_at']);

        foreach ($books as $book) {
            fputcsv($handle, [$book->id, $book->name, $book->created_at]);
        }

        fclose($handle);

        return response()->download($filePath, $csvFileName, $headers)->deleteFileAfterSend(true);
    }

    private function sanitizeAndValidateGenre($genre)
    {
        $genre = trim($genre);
        $genre = ucfirst(strtolower($genre));

        if (!preg_match('/^[a-zA-Z\s]+$/', $genre)) {
            return response()->json(['error' => 'Invalid genre name: ' . $genre], 400);
        }

        return $genre;
    }

    private function sanitizeAndValidateAuthor($author)
    {
        $author = trim($author);

        if (!preg_match('/^[a-zA-Z\s\.]+$/', $author)) {
            return response()->json(['error' => 'Invalid author name: ' . $author], 400);
        }

        return $author;
    }

    private function associateBookWithGenre($book, $genre)
    {
        $genreModel = Genres::firstOrCreate(['genre' => $genre]);
        Book_Genres::create(['book_id' => $book->id, 'genre_id' => $genreModel->id]);
    }

    private function associateBookWithAuthor($book, $author)
    {
        $authorModel = Authors::firstOrCreate(['name' => $author]);
        Book_Authors::create(['book_id' => $book->id, 'author_id' => $authorModel->id]);
    }
}
