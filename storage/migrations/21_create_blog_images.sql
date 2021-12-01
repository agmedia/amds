CREATE TABLE `oc_blog_image` (
                                 `blog_image_id` int(11) NOT NULL,
                                 `blog_id` int(11) NOT NULL,
                                 `image` varchar(255) DEFAULT NULL,
                                 `sort_order` int(3) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `oc_blog_image`
    ADD PRIMARY KEY (`blog_image_id`),
  ADD KEY `blog_id` (`blog_id`);

ALTER TABLE `oc_blog_image`
    MODIFY `blog_image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;