#import "DismissSegue.h"
#import "AppDelegate.h"

@implementation DismissSegue

- (void)dismissCompletion:(NSString *)argDismissedVCName;
{
    // ワーニング対策のダミー
}

- (void)perform {
    UIViewController *sourceViewController = self.sourceViewController;
    NSString *dismissedVCName = NSStringFromClass([sourceViewController class]);
    [sourceViewController.presentingViewController dismissViewControllerAnimated:YES completion:^{
        if ([((AppDelegate*)[UIApplication sharedApplication].delegate) respondsToSelector:@selector(dismissCompletion:)]){
            [((AppDelegate*)[UIApplication sharedApplication].delegate) performSelectorOnMainThread:@selector(dismissCompletion:) withObject:dismissedVCName waitUntilDone:NO];
        }
    }];
}

@end
