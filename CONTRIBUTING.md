# How to contribute to Dolibarr

## Bug reports and feature requests

<a name="not-a-support-forum"></a>_Note_: **GitHub Issues is not a support forum.** If you have questions or need help
using the software, please use [the discussions](https://github.com/milenmk/laravel-rate-limiting/discussions) section.

Issues are managed on [GitHub](https://github.com/milenmk/laravel-rate-limiting/issues).
Default **language here is English**. So please prepare your contributions in English.

1. Please [use the search engine](https://help.github.com/articles/searching-issues) to check if nobody's already
   reported your problem.
2. [Create an issue](https://help.github.com/articles/creating-an-issue). Choose an appropriate title. Prepend
   appropriately with Bug or Feature Request.
3. Tell us the version you are using! (look at /htdocs/admin/system/dolibarr.php? and check if you are using the
   latest version)
4. Write a report with as much detail as possible (Use [screenshots](https://help.github.com/articles/issue-attachments)
   or even screencasts and provide logging and debugging information whenever possible).
5. Delete unnecessary submissions.
6. **Check your Message at Preview before sending.**

## <a name="code"></a>Submit code

### Basic workflow

1. [Fork](https://help.github.com/articles/fork-a-repo)
   the [GitHub repository](https://github.com/milenmk/laravel-rate-limiting).
2. Clone your fork.
3. Choose a branch(See the [Branches](#branches) section below).
4. Commit and push your changes.
5. [Make a pull request](https://help.github.com/articles/creating-a-pull-request).

<span id="branches" name="branches"></span>

### Branches

Unless you're fixing a bug, all pull requests should be made against the _develop_ branch.

If you're fixing a bug, it is preferred that you cook your fix and pull request it against the oldest version affected.

We recommend to push it into N - 2 for N the latest version available, if not possible into version N - 1, and finally
into develop.
This is just a recommendation, currently, if you push a bug fix on a very old version, it is still merged and propagated
into higher versions.

The rule N - 2 is just a tip if you don't know which version to choose to get the best compromise between ease of
correction and number of potential beneficiaries of the correction.

### General rules

Please don't edit the ChangeLog file.

### Commits

Use clear commit messages with the following structure:

```plaintext
[KEYWORD] [ISSUENUM] DESC

LONGDESC
```

where

#### Keyword

In uppercase if you want to have the log comment appears into the generated ChangeLog file.

The keyword can be omitted if your commit does not fit in any of the following categories:

- Fix/FIX: for a bug fix
- Close/CLOSE: for closing a referenced feature request
- New/NEW: for an unreferenced new feature (Opening a feature request and using close is preferred)
- Perf/PERF: for a performance enhancement
- Qual/QUAL: for quality code enhancement or re-engineering

#### Issue num

If your commit fixes a referenced bug or feature request.

In the form of a # followed by the GitHub issue number.

#### Desc

A short description of the commit content.

This should ideally be less than 50 characters.

#### Long Desc

A long description of the commit content.

You can really go to town here and explain in depth what you've been doing.

Feel free to express technical details, use cases or anything relevant to the current commit.

This section can span multiple lines.

If your PR is a change on interface, you must also paste a screenshot showing the new screen.

#### Examples

<pre>
FIX|Fix #456 Short description (where #456 is number of bug fix, if it exists. In upper case to appear into ChangeLog)
or
CLOSE|Close #456 Short description (where #456 is number of feature request, if it exists. In upper case to appear into ChangeLog)
or
NEW|New|QUAL|Qual|PERF|Perf Short description (In upper case to appear into ChangeLog, use this if you add a feature not tracked, otherwise use CLOSE #456)
or
Short description (when the commit is not introducing feature nor closing a bug)

Long description (Can span across multiple lines).
</pre>

### Pull Requests

Pull Request (PR) process is the process to submit a change (enhancement, bug fix, ...) into the code of the project.
There is some rules to know and
a process to follow to optimize the chance to have PRs merged efficiently...

- A PR must be atomic. It means it must contain the lower possible changes for 1 need (1 bug fix or 1 new feature)
  without breaking usability of code. If a PR can be split into several PRs, it often means your PR is not atomic.

- Your Pull Request (PR) must pass the Continuous Integration checks and code quality checks.

- When submitting a pull request, use same rule as [Commits](#commits) for the message. If your pull request only
  contains 1 commit, GitHub will be smart enough to fill it for you. Otherwise, please be a bit verbose about what
  you're providing.

- A screenshot will always be required for any PR of change/addition of a GUI behaviour.

Also, some code changes need a prior approbation:

- if you want to include a new external library / composer package, please ask before adding it the core project
  manager (mention @milenmk in your issue) to see if such a library can be accepted.

Once a PR has been submitted, you may need to wait for its integration. It is common that the project leader let the PR
open for a long delay to allow every developer discuss the PR.

If the label of PR start with "Draft" or "WIP" (Work In Progress), it will not be analyzed for merging until you change
the label of the PR (but it can be analyzed for discussion).

If your PR has errors reported by the Continuous Integration Platform, it means your PR is not valid and nothing will be
done with it. It will be kept open to allow developers to fix this, or it may be closed several month later. Don't
expect anything on your PR if you have such errors, you MUST first fix the Continuous Integration error to have it taken
into consideration.

If the PR is valid, and is kept open for a long time, a tag will also be added on the PR to describe the status of your
PR and why the PR is kept open. By putting your mouse on the tag, you will get a full explanation of the tag/status that
explain why your PR has not been integrated yet.

In most cases, it gives you information of things you have to do to have the PR taken into consideration (for example a
change is requested, a conflict is expected to be solved, some questions were asked). If you have a yellow, red flag of
purple flag, don't expect to have your PR validated. You must first provide the answer the tag ask you. The majority of
open PR are waiting an action of the author of the PR.

## Documentation

The project's documentation is maintained on
the [Packages documentation site](https://minkov.dev/laravel-rate-limiting/documentation).
