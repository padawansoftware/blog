<?php
namespace Admin\Controller;

use Core\Entity\Collection;
use Core\Entity\Post;
use Admin\Form\PostType;
use Admin\Service\Entity\PostService;
use Admin\Service\Entity\AssetService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Admin\Form\ImageAssetType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @Route("/posts")
 */
class PostController extends Controller
{
    /**
     * @Route("/", name="posts_index")
     */
    public function indexAction(PostService $postService)
    {
        $posts = $postService->findAll();

        return $this->render('Post/index.html.twig', [
            'posts' => $posts
        ]);
    }

    /**
     * @Route("/create", name="posts_create")
     */
    public function createAction(Request $request, PostService $postService)
    {
        $post = $postService->create();

        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $postService->persist($post);

            $this->addFlash('success', 'Post created successfully');

            if ($form->get('save&exit')->isClicked()) {
                return $this->redirectToRoute('posts_index');
            } else {
                return $this->redirectToRoute('posts_edit', ['post' => $post->getId()]);
            }
        }

        return $this->render('Post/form.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
            'form_action' => 'create'
        ]);
    }

    /**
     * @Route("/{post}/edit", name="posts_edit")
     */
    public function editAction(Request $request, Post $post, PostService $postService)
    {
        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $postService->update();

            $this->addFlash('success', 'Post edited successfully');

            if ($form->get('save&exit')->isClicked()) {
                return $this->redirectToRoute('posts_index');
            }
        }

        return $this->render('Post/form.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
            'form_action' => 'edit'
        ]);
    }

    /**
     * @Route("/{post}", name="posts_delete", methods={"DELETE"})
     */
    public function deleteAction(Post $post, PostService $postService)
    {
        $postService->remove($post);

        return new Response();
    }

    /**
     * @Route("/_toggle_status", name="_posts_toggle_status")
     */
    public function toggleStatusAction(Request $request, PostService $postService)
    {
        $post = $postService->find($request->get('post', false));
        if ($post instanceof Post) {
            $post->toggleStatus();
            $postService->update();
        }

        return new Response();
    }

    /**
     * @Route("/{post}/_upload_chapter_image", name="_posts_upload_chapter_image", options={"expose"=true})
     */

    public function uploadChapterImageAction(Request $request, Post $post, AssetService $assetService, UploaderHelper $uploaderHelper)
    {
        $asset = $assetService->create();
        $form = $this->createForm(ImageAssetType::class, $asset, ['csrf_protection' => false, 'entity' => $post]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $assetService->persist($asset);

            return new JsonResponse([
                "success" => true,
                "link" => $request->getSchemeAndHttpHost() . $uploaderHelper->asset($asset, 'file')]);
        }

        return new JsonResponse([
            "success" => false
        ]);
    }
}
