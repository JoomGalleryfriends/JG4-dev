# JoomGallery v4.x (Development)

Development repository of the JoomGallery component for Joomla! 4.

**Project-Website:**
https://www.en.joomgalleryfriends.net/

**Support-Forum:**
https://www.forum.joomgalleryfriends.net

**Project-Management:**
https://github.com/orgs/JoomGalleryfriends/projects/1 (Access only for Team-Members)

**Project Presentation:**
https://docs.google.com/presentation/d/1kXGfGRrHswU0M3yh0zUvOwW1fYqB07cksHzNxzcEyxI

## Want to contribute?

JoomGallery is an OpenSource project and is developed by users for users. So if you are using JoomGallery feel free to contribute to the project...


[![](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/webapps/shoppingcart?flowlogging_id=0680203b93484&mfid=1668585189660_0680203b93484#/checkout/openButton)

## Code development
### Joomla 4 extension development docs
- https://blog.astrid-guenther.de/en/der-weg-zu-joomla4-erweiterungen/
- https://www.dionysopoulos.me/book.html
- https://joomlacommunity.cloud.mattermost.com/main/channels/extension-development-room

### Codestyle guide
PHP: [Codestyle guide for PHP](docs/Codestyleguide.md)

### Setup development environment
https://docs.joomla.org/Setting_up_your_workstation_for_Joomla_development

**Webserver recommendation:**
- https://wampserver.aviatechno.net/ (Windows only)
- https://www.apachefriends.org/index.html (Windows, Linux and macOS)

**IDE/Editor recommendation:**
- https://www.jetbrains.com/phpstorm/ (Windows, Linux and macOS)
- https://code.visualstudio.com/ (Windows, Linux and macOS)

**Git-Client recommendation:**
- https://desktop.github.com/ (Windows and macOS)

**Recommendet approach for proper versioning with Git:**
1. Checkout the repo into a folder of your choice
2. Download the source code of the dev-branch as zip file and install it on Joomla
3. Remove the installed component folders within your Joomla installation
   - administrator/components/com_joomgallery
   - components/com_joomgallery
   - media/com_joomgallery
   - plugins/finder/joomgallerycategories
   - plugins/finder/joomgalleryimages
   - plugins/privacy/joomgalleryimages
   - plugins/webservices/joomgallery
4. Create symbolic links from those folders to the corresponding folders within the checked out copy of your component
5. Remove the installed component language files within your Joomla installation
   - administrator/language/en-GB/com_joomgallery.ini
   - administrator/language/en-GB/com_joomgallery.exif.ini
   - administrator/language/en-GB/com_joomgallery.iptc.ini
   - administrator/language/en-GB/com_joomgallery.sys.ini
   - language/en-GB/com_joomgallery.ini
6. Create symbolic links from those files to the corresponding files within the checked out copy of your component
7. The referenced copy of your component can be properly versioned using Git

**Symbolic link generator tool for windows:**
https://schinagl.priv.at/nt/hardlinkshellext/linkshellextension.html

### Task areas during development

| Taks area | Description |
| ----------- | ----------- |
| PHP Devloper | Programming the functionalities of the JoomGallery (Framework, MVC pattern, Services, Helpers, ...) |
| Frontend Designer | Creating the template files for the component views in the frontend (Template: Cassiopeia) |
| Backend Designer | Creating the template files for the component views in the backend (Template: Atum) |
| Language Manager | Setting up, structuring and managing the language files for frontend and backend. |
| Documentation | Writing instructions for the support section of the website. |
| Testing | Testing the new code before merging them into the main project. |

## Testing
1. Open the Pull request you want to test
2. Change to the branch where the code of the PR is coming from
3. Button "Code"->"Download ZIP"
4. Install the zip file in your Joomla
5. Perform tests where the Pull request changes anything
6. If you find a bug or unexpected behaviour, post a comment in the pull request with the following content:

#### Steps to reproduce the issue
List the steps to perfom in order to reproduce the issue you found
#### Expected result
What yould you have expected to happen?
#### Actual result
What did really happen?
#### System information
- PHP-Version
- Database type and version
- (ImageMagick version)
#### Additional comments
Anything else that you think is important for the developer to fix the issue
