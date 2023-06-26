## How to make a release

1. Pull the latest version of master or int branch.
2. Identify the commit hash of the version of the code which will be the cutoff point / the last commit to be pushed to production. The hashes can be found on the Bitbucket repository commits or simply type in ‘git log’ in Git Bash to see the latest commits.
3. On Git Bash, add the following commands:
   > git tag -a v[release version] [commit hash]
   >>  Example: git tag -a v1.0 9zseo10
5. You’ll be taken to a vim editor. Press ‘i’, then type in your commit message. Type ‘wq!’ which will save and close the editor.
6. Push the release version to BitBucket:
   > git push origin v[release version]
   >
   >> Example: git push origin v1.0