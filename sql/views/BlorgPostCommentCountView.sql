create or replace view BlorgPostCommentCountView as
	select post, count(id) as comment_count,
			max(createdate) as last_comment_date
		from BlorgComment
		where status = 1 and spam = false -- status 1 is published
		group by post;
