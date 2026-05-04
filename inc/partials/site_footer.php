<?php
declare(strict_types=1);
$year = (int) date('Y');
?>
<footer class="mt-16 border-t border-zinc-200 bg-zinc-50/80 py-10">
  <div class="mx-auto max-w-3xl px-4 sm:px-6">
    <nav class="mb-3 flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-600" aria-label="Footer">
      <a href="/about" class="font-medium text-zinc-600 hover:text-teal-700">About</a>
      <a href="/privacy" class="font-medium text-zinc-600 hover:text-teal-700">Privacy</a>
      <a href="/terms" class="font-medium text-zinc-600 hover:text-teal-700">Terms</a>
      <a href="/contact" class="font-medium text-zinc-600 hover:text-teal-700">Contact</a>
    </nav>
    <p class="text-sm text-zinc-500">© <?= $year ?> <a href="https://hillwork.us" class="font-medium text-teal-700 hover:text-teal-800">Hillwork, LLC</a></p>
  </div>
</footer>
