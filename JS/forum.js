document.addEventListener("DOMContentLoaded", async () => {
  const postThreadBtn = document.getElementById("post-thread");
  const threadsContainer = document.getElementById("threads-container");

  // Fetch and render threads
  async function fetchThreads() {
    const response = await fetch("../PHP/forum.php?action=getThreads");
    const threads = await response.json();

    threadsContainer.innerHTML = ""; // Clear container before rendering

        // Loop through each thread and render it on the page
    threads.forEach((thread) => {
      const threadElement = document.createElement("div");
      threadElement.className = "thread";
      threadElement.dataset.id = thread.id; // Store thread ID for comments

      // Render thread details and comments
      threadElement.innerHTML = `
          <h3>${thread.title}</h3>
          <p>${thread.content}</p>
          <p><strong>Posted by:</strong> ${thread.firstname} ${thread.lastname}</p>
          <div class="comments">
              <input type="text" class="comment-input" placeholder="Write a comment..." />
              <button class="add-comment">Add Comment</button>
              <ul class="comments-list">
                  ${thread.comments
                    .map(
                      (comment) =>
                        `<li><strong>${comment.firstname} ${comment.lastname}:</strong> ${comment.text}</li>`
                    )
                    .join("")}
              </ul>
          </div>
      `;
      // Append the thread element to the threads container
      threadsContainer.appendChild(threadElement);
    });
  }

  // Add an event listener to the "post-thread" button for creating new threads
  postThreadBtn.addEventListener("click", async () => {
    const title = document.getElementById("thread-title").value.trim();
    const content = document.getElementById("thread-content").value.trim();

    if (!title || !content) {
      alert("Please fill in both the title and content!");
      return;
    }
    // Send a request to the server to add the new thread
    const response = await fetch("../PHP/forum.php?action=addThread", {
      method: "POST",
      body: new URLSearchParams({ title, content }),
    });

    const data = await response.json();
    if (data.success) {
      await fetchThreads(); // Reload threads after adding
      document.getElementById("thread-title").value = ""; // Clear input fields
      document.getElementById("thread-content").value = "";
    } else {
      alert("Error adding thread: " + data.error);
    }
  });

  // Add a comment to a thread
  threadsContainer.addEventListener("click", async (event) => {
    if (event.target.classList.contains("add-comment")) {
      const thread = event.target.closest(".thread");
      const commentInput = thread.querySelector(".comment-input");
      const commentText = commentInput.value.trim();

      if (!commentText) {
        alert("Please write a comment!");
        return;
      }

      const threadId = thread.dataset.id; // Get the thread ID

      const response = await fetch("../PHP/forum.php?action=addComment", {
        method: "POST",
        body: new URLSearchParams({
          thread_id: threadId,
          text: commentText,
        }),
      });

      const data = await response.json();
      if (data.success) {
        await fetchThreads(); // Reload threads and comments after adding a comment
      } else {
        alert("Error adding comment: " + data.error);
      }
    }
  });

  // Initial fetch of threads
  await fetchThreads();
});
