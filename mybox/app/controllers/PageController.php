<?php
class PageController extends Controller
{
    public function notFound(): void
    {
        $this->render('errors/not-found', ['message' => 'La página solicitada no existe.']);
    }
}
