<?php

// src/Controller/Admin/AdminPostController.php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class AdminUserController extends AbstractController
{
    /**
     * @Route("/users", name="admin_user_index")
     */
    public function index()
    {
        // Fetch posts from the database
        // Render the template with posts data
        return $this->render('admin/users/index.html.twig');
    }

    // Add methods for creating, editing, and deleting posts
}
