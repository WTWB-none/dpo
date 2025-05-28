<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookFormType;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/book")]
class BookController extends AbstractController
{
    private function handleFileUpload(
        mixed $file,
        string $parameterName,
        Book $book,
        SluggerInterface $slugger,
        EntityManagerInterface $entityManager,
        string $type
    ): bool {
        if ($file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . "-" . uniqid() . "." . $file->guessExtension();
            $dateDir = (new \DateTime())->format("Y/m");
            $targetDirectory = $this->getParameter($parameterName) . "/" . $dateDir;

            $filesystem = new Filesystem();
            $oldFilePath = null;
            if ($type === "cover" && $book->getCoverPath()) {
                $oldFilePath = $this->getParameter("covers_directory") . "/" . $book->getCoverPath();
            } elseif ($type === "bookFile" && $book->getFilePath()) {
                $oldFilePath = $this->getParameter("books_directory") . "/" . $book->getFilePath();
            }

            if ($oldFilePath && $filesystem->exists($oldFilePath)) {
                $filesystem->remove($oldFilePath);
            }

            try {
                if (!file_exists($targetDirectory)) {
                    mkdir($targetDirectory, 0777, true);
                }
                $file->move($targetDirectory, $newFilename);
                if ($type === "cover") {
                    $book->setCoverPath($dateDir . "/" . $newFilename);
                    $book->setCoverFilename($file->getClientOriginalName());
                } elseif ($type === "bookFile") {
                    $book->setFilePath($dateDir . "/" . $newFilename);
                    $book->setOriginalFilename($file->getClientOriginalName());
                }
            } catch (FileException $e) {
                $this->addFlash("error", "Ошибка при загрузке файла (" . $type . "): " . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    #[Route("/new", name: "app_book_new", methods: ["GET", "POST"])]
    #[IsGranted("IS_AUTHENTICATED_REMEMBERED")]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $book = new Book();
        $form = $this->createForm(BookFormType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $book->setUser($this->getUser());

            $coverFile = $form->get("coverFile")->getData();
            if (!$this->handleFileUpload($coverFile, "covers_directory", $book, $slugger, $entityManager, "cover")) {
            }

            $bookFile = $form->get("bookFile")->getData();
            if (!$this->handleFileUpload($bookFile, "books_directory", $book, $slugger, $entityManager, "bookFile")) {
            }

            $entityManager->persist($book);
            $entityManager->flush();

            $this->addFlash("success", "Книга успешно добавлена!");
            return $this->redirectToRoute("app_home");
        }

        return $this->render("book/new.html.twig", [
            "book" => $book,
            "form" => $form->createView(),
        ]);
    }

    #[Route("/{id}/edit", name: "app_book_edit", methods: ["GET", "POST"])]
    #[IsGranted("IS_AUTHENTICATED_REMEMBERED")]
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager, SluggerInterface $slugger, Filesystem $filesystem): Response
    {
        if ($book->getUser() !== $this->getUser() && !$this->isGranted("ROLE_ADMIN")) {
            throw $this->createAccessDeniedException("У вас нет прав для редактирования этой книги.");
        }

        $form = $this->createForm(BookFormType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has("deleteCoverFile") && $form->get("deleteCoverFile")->getData()) {
                if ($book->getCoverPath()) {
                    $oldCoverPath = $this->getParameter("covers_directory") . "/" . $book->getCoverPath();
                    if ($filesystem->exists($oldCoverPath)) {
                        $filesystem->remove($oldCoverPath);
                    }
                    $book->setCoverPath(null);
                    $book->setCoverFilename(null);
                }
            }
            $coverFile = $form->get("coverFile")->getData();
            if ($coverFile) {
                if (!$this->handleFileUpload($coverFile, "covers_directory", $book, $slugger, $entityManager, "cover")) {
                }
            }

            if ($form->has("deleteBookFile") && $form->get("deleteBookFile")->getData()) {
                if ($book->getFilePath()) {
                    $oldBookFilePath = $this->getParameter("books_directory") . "/" . $book->getFilePath();
                    if ($filesystem->exists($oldBookFilePath)) {
                        $filesystem->remove($oldBookFilePath);
                    }
                    $book->setFilePath(null);
                    $book->setOriginalFilename(null);
                }
            }
            $bookFile = $form->get("bookFile")->getData();
            if ($bookFile) {
                if (!$this->handleFileUpload($bookFile, "books_directory", $book, $slugger, $entityManager, "bookFile")) {
                }
            }

            $entityManager->flush();
            $this->addFlash("success", "Книга успешно обновлена!");
            return $this->redirectToRoute("app_home");
        }

        return $this->render("book/edit.html.twig", [
            "book" => $book,
            "form" => $form->createView(),
        ]);
    }

    #[Route("/{id}", name: "app_book_delete", methods: ["POST", "DELETE"])]
    #[IsGranted("IS_AUTHENTICATED_REMEMBERED")]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager, Filesystem $filesystem): Response
    {
        if ($book->getUser() !== $this->getUser() && !$this->isGranted("ROLE_ADMIN")) {
            throw $this->createAccessDeniedException("У вас нет прав для удаления этой книги.");
        }

        if ($this->isCsrfTokenValid("delete" . $book->getId(), $request->request->get("_token"))) {
            if ($book->getCoverPath()) {
                $coverPath = $this->getParameter("covers_directory") . "/" . $book->getCoverPath();
                if ($filesystem->exists($coverPath)) {
                    $filesystem->remove($coverPath);
                }
            }
            if ($book->getFilePath()) {
                $filePath = $this->getParameter("books_directory") . "/" . $book->getFilePath();
                if ($filesystem->exists($filePath)) {
                    $filesystem->remove($filePath);
                }
            }

            $entityManager->remove($book);
            $entityManager->flush();
            $this->addFlash("success", "Книга успешно удалена!");
        }

        return $this->redirectToRoute("app_home");
    }

    #[Route("/{id}/download", name: "app_book_download", methods: ["GET"])]
    public function download(Book $book): Response
    {
        if (!$book->isAllowDownload() || !$book->getFilePath()) {
            throw $this->createAccessDeniedException("Скачивание этой книги не разрешено.");
        }

        $filePath = $this->getParameter("books_directory") . "/" . $book->getFilePath();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException("Файл книги не найден.");
        }
        return $this->file($filePath, $book->getOriginalFilename());
    }
}

