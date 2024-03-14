<?php

// src/Controller/Admin/AdminPostController.php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AdminPanelController extends AbstractController
{
    /**
     * @Route("/admin", name="admin_panel_index")
     */
    public function index()
    {
        // Fetch posts from the database
        // Render the template with posts data
        return $this->render('admin/index.html.twig');
    }

    // Add methods for creating, editing, and deleting posts
}