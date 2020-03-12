
### Note : This project is not finalized. Please check [cut-api.com](cut-api.com) if you need a cut optimization solution. cut-api.com offers an API with performant cut optimization algorithm which you can easily setup.


This is a sample of code build with the YII framework. It is an advanced 2D cut optimizer (best fitting multiple pieces on a panel to optimize the cut with a saw).

This project is not finalized.

The main algorithm is in the file controllers/OptimizerController.php.

Beside using YII (PHP), this tool has a compiled Linux program that take in charge the actual cut optimization. With this setup a better performance is achieved for this "Knapsack problem". This library has been bought to an external party (optimalprograms) and is integrated with the tool. Some specific logics have been applied on top of the cut optimization to match to company's goals.
